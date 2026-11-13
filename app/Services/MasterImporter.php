<?php

namespace App\Services;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MasterImporter
{
    // Nama kolom CSV yang dikenali (sudah dinormalisasi: huruf kecil, spasi jadi _).
    // NIK_BAM sengaja tidak ada: data pribadi karyawan, tidak dipakai dashboard.
    private const ALIASES = [
        'nipnas'        => ['nipnas'],
        'name'          => ['standadrt_name', 'standard_name', 'standart_name', 'nama_pelanggan', 'name'],
        'industry'      => ['industri_ho', 'industri'],
        'subsegment'    => ['subsegmen_ho', 'subsegmen'],
        'account_num'   => ['account_num', 'account_number'],
        'product_group' => ['product_group'],
        'product_name'  => ['product_name'],
        'circuit_speed' => ['circuit_speed'],
        'contract_no'   => ['contract_no'],
        'start_date'    => ['start_dat', 'start_date'],
        'end_date'      => ['end_dat', 'end_date'],
        'abonemen'      => ['abonemen'],
        'group1'        => ['group1'],
        'group2'        => ['group2'],
        'group3'        => ['group3'],
        'group4'        => ['group4'],
        'group5'        => ['group5'],
        'revenue'       => ['revenue'],
        'am_name'       => ['nama_bam'],
        'order_date'    => ['cust_order_completedate'],
        'segmen_bill'   => ['segmen_bill_old'],
        'sid_date'      => ['sid_completedate'],
        'datel'         => ['sa_datel'],
        'sto'           => ['sa_sto'],
    ];

    /**
     * Import data master dari satu file CSV.
     *
     * @param  int  $revenueMonths  1 = nilai REVENUE di file sudah per bulan, 12 = per tahun
     * @return array{rows:int,customers:int,created:int,updated:int,products:int,skipped:int}
     *
     * @throws \RuntimeException kalau file kosong / kolom nama pelanggan tidak ketemu
     */
    public function import(string $file, int $revenueMonths = 1): array
    {
        $revenueMonths = max(1, $revenueMonths);

        // ---------- 1) Baca file & kelompokkan per pelanggan (NIPNAS) ----------
        $customers = [];
        $total     = 0;
        $skipped   = 0;

        foreach ($this->rows($file) as $r) {
            $total++;
            $name = $r['name'] ?? '';

            if ($name === '') {
                $skipped++;
                continue;
            }

            $nipnas = preg_replace('/\.0+$/', '', $r['nipnas'] ?? ''); // Excel kadang menulis 1234.0
            $key    = $nipnas !== '' ? $nipnas : 'N-' . substr(md5(Str::lower($name)), 0, 10);

            $c = $customers[$key] ?? [
                'code'        => $key,
                'name'        => $name,
                'industry'    => null,
                'subsegment'  => null,
                'datel'       => null,
                'sto'         => null,
                'am'          => null,
                'account'     => null,
                'first_order' => null,
                'revenue'     => 0,
                'products'    => [],
            ];

            $c['industry']   ??= $this->val($r, 'industry');
            $c['subsegment'] ??= $this->val($r, 'subsegment');
            $c['datel']      ??= $this->val($r, 'datel');
            $c['sto']        ??= $this->val($r, 'sto');
            $c['am']         ??= $this->val($r, 'am_name');
            $c['account']    ??= $this->val($r, 'account_num');

            $order = $this->date($r['order_date'] ?? '') ?? $this->date($r['start_date'] ?? '');
            if ($order && (! $c['first_order'] || $order->lt($c['first_order']))) {
                $c['first_order'] = $order;
            }

            $revenue       = $this->num($r['revenue'] ?? 0);
            $c['revenue'] += $revenue;

            $c['products'][] = [
                'account_num'       => $this->val($r, 'account_num'),
                'product_group'     => $this->val($r, 'product_group'),
                'product_name'      => $this->val($r, 'product_name'),
                'circuit_speed'     => $this->val($r, 'circuit_speed'),
                'contract_no'       => $this->val($r, 'contract_no'),
                'start_date'        => $this->date($r['start_date'] ?? '')?->toDateString(),
                'end_date'          => $this->date($r['end_date'] ?? '')?->toDateString(),
                'sid_complete_date' => $this->date($r['sid_date'] ?? '')?->toDateString(),
                'abonemen'          => ($r['abonemen'] ?? '') === '' ? null : $this->num($r['abonemen']),
                'revenue'           => $revenue,
                'extra'             => json_encode(array_filter([
                    'group1'      => $this->val($r, 'group1'),
                    'group2'      => $this->val($r, 'group2'),
                    'group3'      => $this->val($r, 'group3'),
                    'group4'      => $this->val($r, 'group4'),
                    'group5'      => $this->val($r, 'group5'),
                    'segmen_bill' => $this->val($r, 'segmen_bill'),
                ])),
            ];

            $customers[$key] = $c;
        }

        // ---------- 2) Simpan per 200 pelanggan ----------
        $now          = now();
        $created      = 0;
        $productCount = 0;

        foreach (array_chunk($customers, 200) as $chunk) {
            $codes    = array_column($chunk, 'code');
            $existing = Customer::whereIn('customer_code', $codes)->count();
            $created += count($chunk) - $existing;

            DB::table('customers')->upsert(
                array_map(fn ($c) => [
                    'customer_code'   => $c['code'],
                    'name'            => $c['name'],
                    'industry'        => $c['industry'],
                    'subsegment'      => $c['subsegment'],
                    'wilayah'         => $c['datel'] ?? '-',
                    'sto'             => $c['sto'],
                    'am_name'         => $c['am'],
                    'internet_number' => $c['account'],
                    'product'         => Str::limit(
                        collect($c['products'])->pluck('product_name')->filter()->unique()->implode(', ') ?: '-',
                        250,
                        ''
                    ),
                    'status'          => 'aktif',
                    'install_date'    => ($c['first_order'] ?? today())->toDateString(),
                    'phone'           => null,
                    'address'         => null,
                    'monthly_revenue' => (int) round($c['revenue'] / $revenueMonths),
                    'health_score'    => 0,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ], $chunk),
                ['customer_code'],
                // status, phone, address, health_score sengaja tidak ditimpa kalau pelanggan sudah ada
                ['name', 'industry', 'subsegment', 'wilayah', 'sto', 'am_name', 'internet_number', 'product', 'install_date', 'monthly_revenue', 'updated_at']
            );

            $ids = Customer::whereIn('customer_code', $codes)->pluck('id', 'customer_code');

            // Import ulang = ganti daftar layanan pelanggan itu (tidak dobel)
            DB::table('customer_products')->whereIn('customer_id', $ids->values())->delete();

            $rows = [];
            foreach ($chunk as $c) {
                $id = $ids[$c['code']] ?? null;

                if (! $id) {
                    continue;
                }

                foreach ($c['products'] as $p) {
                    $rows[] = $p + ['customer_id' => $id, 'created_at' => $now, 'updated_at' => $now];
                }
            }

            foreach (array_chunk($rows, 200) as $part) {
                DB::table('customer_products')->insert($part);
            }

            $productCount += count($rows);
        }

        Cache::forget('topbar.notifications');

        return [
            'rows'      => $total,
            'customers' => count($customers),
            'created'   => $created,
            'updated'   => count($customers) - $created,
            'products'  => $productCount,
            'skipped'   => $skipped,
        ];
    }

    // ============================================================ PEMBACA CSV
    private function rows(string $file): \Generator
    {
        $h = fopen($file, 'r');

        if (! $h) {
            throw new \RuntimeException('File tidak bisa dibuka.');
        }

        // Pemisah: koma, titik koma, atau TAB (mana yang paling banyak di baris header)
        $firstLine = (string) fgets($h);
        rewind($h);

        $delimiter = ',';
        $best      = 0;
        foreach ([',', ';', "\t"] as $d) {
            $n = substr_count($firstLine, $d);
            if ($n > $best) {
                $best      = $n;
                $delimiter = $d;
            }
        }

        $header = fgetcsv($h, 0, $delimiter, '"', '\\');

        if (! $header) {
            throw new \RuntimeException('File kosong atau tidak bisa dibaca.');
        }

        $header[0]  = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]); // buang BOM
        $normalized = array_map(fn ($c) => Str::slug((string) $c, '_'), $header);

        $map = [];
        foreach (self::ALIASES as $field => $names) {
            foreach ($names as $name) {
                $i = array_search($name, $normalized, true);

                if ($i !== false) {
                    $map[$field] = $i;
                    break;
                }
            }
        }

        if (! isset($map['name'])) {
            throw new \RuntimeException(
                'Kolom nama pelanggan (STANDART NAME / STANDARD_NAME) tidak ketemu. Header yang terbaca: ' . implode(', ', $normalized)
            );
        }

        while (($row = fgetcsv($h, 0, $delimiter, '"', '\\')) !== false) {
            if ($row === [null]) {
                continue; // baris kosong
            }

            $out = [];
            foreach ($map as $field => $i) {
                $out[$field] = trim((string) ($row[$i] ?? ''));
            }

            yield $out;
        }

        fclose($h);
    }

    // ============================================================ HELPER
    private function val(array $r, string $key): ?string
    {
        $x = $r[$key] ?? '';

        return $x === '' ? null : $x;
    }

    // "Rp 350.000", "350000,00", "1.100.000" -> integer rupiah
    private function num(mixed $v): int
    {
        $v = preg_replace('/[^\d.,]/', '', (string) $v);
        $v = preg_replace('/[.,]\d{1,2}$/', '', $v); // buang desimal (",00" / ".50")

        return (int) preg_replace('/\D/', '', $v);
    }

    private function date(?string $v): ?Carbon
    {
        $v = trim((string) $v);

        if ($v === '') {
            return null;
        }

        // Format Oracle: "12-NOV-23 03.00.00.0000000000 PM" -> buang bagian jamnya
        $v = preg_replace('/\s+\d{1,2}\.\d{2}\.\d{2}(\.\d+)?\s*(AM|PM)?$/i', '', $v);

        // Bulan Indonesia -> Inggris, supaya format seperti 01-AGU-26 terbaca
        $v = str_ireplace(['Mei', 'Agu', 'Okt', 'Des'], ['May', 'Aug', 'Oct', 'Dec'], $v);

        $formats = [
            'Ymd', // 20201201
            'Y-m-d H:i:s', 'Y-m-d', 'Y/m/d',
            'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'd-m-Y', 'j/n/Y',
            'd-M-y', 'd-M-Y', 'j-M-y', 'd M Y', 'j F Y',
        ];

        foreach ($formats as $format) {
            try {
                $d = Carbon::createFromFormat('!' . $format, $v);

                if ($d) {
                    return $d;
                }
            } catch (\Throwable $e) {
                // coba format berikutnya
            }
        }

        return null;
    }
}