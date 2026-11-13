<?php

namespace App\Services;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentImporter
{
    // Nama kolom CSV yang dikenali (sudah dinormalisasi: huruf kecil, spasi jadi _)
    private const ALIASES = [
        'customer_code' => ['nipnas', 'customer_code', 'id_pelanggan', 'kode_pelanggan'],
        'invoice_date'  => ['tanggal_tagihan', 'tgl_tagihan', 'invoice_date'],
        'paid_date'     => ['tanggal_pembayaran', 'tanggal_bayar', 'tgl_bayar', 'paid_date'],
        'amount'        => ['jumlah_tagihan', 'nominal', 'amount', 'tagihan'],
    ];

    /**
     * Import riwayat pembayaran dari satu file CSV.
     * Keterlambatan & status dihitung dari tanggal (kolom KETERLAMBATAN/STATUS di file diabaikan):
     *  - tanggal bayar kosong        -> tunggakan
     *  - bayar di tanggal tagihan    -> tepat waktu
     *  - bayar setelah tanggal tagihan -> terlambat
     *
     * @return array{rows:int,customers:int,inserted:int,unknown_rows:int,unknown_codes:array,bad_date:int,future:int}
     *
     * @throws \RuntimeException kalau file kosong / kolom wajib tidak ketemu
     */
    public function import(string $file): array
    {
        $ids     = Customer::pluck('id', 'customer_code');
        $revenue = Customer::pluck('monthly_revenue', 'id');
        $today   = today();

        $batch    = [];
        $affected = [];
        $stats    = ['rows' => 0, 'unknown_rows' => 0, 'unknown_codes' => [], 'bad_date' => 0, 'future' => 0];

        foreach ($this->rows($file) as $r) {
            $stats['rows']++;

            $code = $this->code($r['customer_code'] ?? '');
            $id   = $ids->get($code);

            if (! $id) {
                $stats['unknown_rows']++;
                $stats['unknown_codes'][$code] = true;
                continue;
            }

            $invoice = $this->date($r['invoice_date'] ?? '');

            if (! $invoice) {
                $stats['bad_date']++;
                continue;
            }

            $paid = $this->date($r['paid_date'] ?? '');

            // Tagihan yang belum terbit dan belum dibayar bukan tunggakan
            if (! $paid && $invoice->gt($today)) {
                $stats['future']++;
                continue;
            }

            $delay = max(0, (int) $invoice->diffInDays($paid ?? $today, false));

            $amount = ($r['amount'] ?? '') !== ''
                ? $this->num($r['amount'])
                : (int) ($revenue[$id] ?? 0);

            $batch[$id . '|' . $invoice->toDateString()] = [
                'customer_id'  => $id,
                'invoice_date' => $invoice->toDateString(),
                'paid_date'    => $paid?->toDateString(),
                'amount'       => $amount,
                'delay_days'   => $delay,
                'status'       => ! $paid ? 'tunggakan' : ($delay === 0 ? 'tepat_waktu' : 'terlambat'),
                'created_at'   => now(),
                'updated_at'   => now(),
            ];

            $affected[$id] = true;
        }

        if ($batch) {
            DB::transaction(function () use ($batch, $affected) {
                $customerIds = array_keys($affected);

                // Riwayat lama pelanggan yang ada di file diganti seluruhnya (tidak dobel)
                foreach (array_chunk($customerIds, 400) as $chunk) {
                    DB::table('payments')->whereIn('customer_id', $chunk)->delete();
                }

                foreach (array_chunk(array_values($batch), 100) as $part) {
                    DB::table('payments')->insert($part);
                }

                $this->updateHealthScores($customerIds);
            });

            Cache::forget('topbar.notifications');
        }

        return [
            'rows'          => $stats['rows'],
            'customers'     => count($affected),
            'inserted'      => count($batch),
            'unknown_rows'  => $stats['unknown_rows'],
            'unknown_codes' => array_slice(array_keys($stats['unknown_codes']), 0, 5),
            'bad_date'      => $stats['bad_date'],
            'future'        => $stats['future'],
        ];
    }

    // Health score dihitung ulang dari riwayat pembayaran (rumus sama dengan seeder)
    private function updateHealthScores(array $customerIds): void
    {
        foreach (array_chunk($customerIds, 400) as $chunk) {
            $counts = DB::table('payments')
                ->whereIn('customer_id', $chunk)
                ->whereIn('status', ['terlambat', 'tunggakan'])
                ->select('customer_id', 'status', DB::raw('COUNT(*) as n'))
                ->groupBy('customer_id', 'status')
                ->get()
                ->groupBy('customer_id');

            foreach ($chunk as $id) {
                $g      = $counts->get($id, collect());
                $late   = (int) ($g->firstWhere('status', 'terlambat')->n ?? 0);
                $unpaid = (int) ($g->firstWhere('status', 'tunggakan')->n ?? 0);

                DB::table('customers')->where('id', $id)->update([
                    'health_score' => max(30, 100 - ($late * 4) - ($unpaid * 20)),
                ]);
            }
        }
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

        if (! isset($map['customer_code']) || ! isset($map['invoice_date'])) {
            throw new \RuntimeException(
                'Kolom wajib NIPNAS dan/atau TANGGAL_TAGIHAN tidak ketemu. Header yang terbaca: ' . implode(', ', $normalized)
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
    private function code(string $v): string
    {
        $v = trim(ltrim(trim($v), '#'));

        return preg_replace('/\.0+$/', '', $v); // Excel kadang menulis 1234.0
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