<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Collection;

class PaymentBehaviorService
{
    // Reminder dikirim berapa hari sebelum perkiraan tanggal bayar
    public const REMINDER_LEAD_DAYS = 2;

    // [label, kelas badge]
    public const POLA = [
        'rutin'        => ['Rutin',        'badge-green'],
        'kadang_telat' => ['Kadang Telat', 'badge-yellow'],
        'sering_telat' => ['Sering Telat', 'badge-orange'],
        'menunggak'    => ['Menunggak',    'badge-red'],
    ];

    public const STATUS = [
        'tepat_waktu' => ['Tepat waktu', 'badge-green'],
        'terlambat'   => ['Terlambat',   'badge-yellow'],
        'tunggakan'   => ['Tunggakan',   'badge-orange'],
    ];

    public function __construct(private CustomerAnalyzer $analyzer)
    {
    }

    /**
     * Analisis semua pelanggan (payments di-eager load, tanpa N+1 query).
     */
    public function analyzeAll(): Collection
    {
        return Customer::with('payments')
            ->orderBy('id')
            ->get()
            ->map(fn (Customer $c) => $this->analyze($c));
    }

    /**
     * Analisis perilaku bayar satu pelanggan.
     */
    public function analyze(Customer $customer): array
    {
        $b        = $this->analyzer->paymentBehavior($customer);
        $payments = $b['payments'];
        $total    = $payments->count();
        $paid     = $total - $b['unpaid_count'];

        // Dari tagihan yang sudah dibayar, berapa persen yang telat
        $lateRatio = $paid > 0 ? $b['late_count'] / $paid : 0;

        $pola = match (true) {
            $b['unpaid_count'] > 0 => 'menunggak',
            $lateRatio >= 0.7      => 'sering_telat',
            $lateRatio >= 0.3      => 'kadang_telat',
            default                => 'rutin',
        };

        // Biasanya rutin, tapi sekarang menunggak = kondisi tidak normal
        $abnormal = $b['unpaid_count'] > 0 && $paid >= 3 && $lateRatio < 0.3;

        // Reminder: perkiraan tanggal bayar tagihan berikutnya
        $reminder = null;
        $latest   = $payments->last();

        if ($customer->status === 'aktif' && $latest && $latest->status !== 'tunggakan' && $b['typical_day']) {
            $nextInvoice = $latest->invoice_date->copy()->addMonthNoOverflow();
            $expected    = $nextInvoice->copy()->day(min($b['typical_day'], $nextInvoice->daysInMonth));

            $reminder = [
                'expected' => $expected,
                'send'     => $expected->copy()->subDays(self::REMINDER_LEAD_DAYS),
            ];
        }

        [$statusLabel, $statusClass] = self::STATUS[$b['latest_status']] ?? ['-', 'badge-gray'];
        [$polaLabel, $polaClass]     = self::POLA[$pola];

        return [
            'customer'       => $customer,
            'total'          => $total,
            'paid_count'     => $paid,
            'late_count'     => $b['late_count'],
            'unpaid_count'   => $b['unpaid_count'],
            'unpaid_amount'  => $b['unpaid_amount'],
            'avg_delay'      => $b['avg_delay'],
            'typical_day'    => $b['typical_day'],
            'late_ratio'     => $lateRatio,
            'pola'           => $pola,
            'pola_label'     => $polaLabel,
            'pola_class'     => $polaClass,
            'abnormal'       => $abnormal,
            'status_label'   => $statusLabel,
            'status_class'   => $statusClass,
            'reminder'       => $reminder,
        ];
    }

    /**
     * Angka untuk 4 kartu ringkasan + komposisi pola.
     */
    public function summary(Collection $rows): array
    {
        $late = $rows->where('late_count', '>', 0);

        $polaCounts = array_fill_keys(array_keys(self::POLA), 0);
        foreach ($rows as $r) {
            $polaCounts[$r['pola']]++;
        }

        return [
            'monitored'   => $rows->filter(fn ($r) => $r['customer']->status === 'aktif')->count(),
            'need_action' => $rows->where('unpaid_count', '>', 0)->count(),
            'avg_delay'   => $late->isEmpty() ? 0 : round($late->avg('avg_delay'), 1),
            'rutin_pct'   => $rows->isEmpty() ? 0 : (int) round($polaCounts['rutin'] / $rows->count() * 100),
            'pola_counts' => $polaCounts,
        ];
    }

    /**
     * Daftar peringatan + rekomendasi tindakan (hasil analisis, bukan sekadar notifikasi).
     */
    public function notifications(Collection $rows): array
    {
        $items = [];

        foreach ($rows as $r) {
            $c  = $r['customer'];
            $rp = 'Rp ' . number_format($r['unpaid_amount'], 0, ',', '.');

            if ($r['abnormal']) {
                $items[] = [
                    'type'        => 'abnormal',
                    'priority'    => 1,
                    'tone'        => 'red',
                    'icon'        => '!',
                    'title'       => 'Kondisi Tidak Normal',
                    'badge'       => ['Tidak Normal', 'badge-red'],
                    'customer_id' => $c->id,
                    'amount'      => $r['unpaid_amount'],
                    'text'        => "{$c->name} biasanya rutin membayar (telat {$r['late_count']} dari {$r['paid_count']} tagihan sebelumnya), tetapi kini menunggak {$r['unpaid_count']} bulan ({$rp}).",
                    'action'      => 'Hubungi pelanggan segera untuk menanyakan kendala pembayaran.',
                ];
            } elseif ($r['unpaid_count'] > 0) {
                $items[] = [
                    'type'        => 'tunggakan',
                    'priority'    => 2,
                    'tone'        => 'red',
                    'icon'        => '!',
                    'title'       => 'Payment Warning',
                    'badge'       => ['Tunggakan', 'badge-orange'],
                    'customer_id' => $c->id,
                    'amount'      => $r['unpaid_amount'],
                    'text'        => "{$c->name} menunggak {$r['unpaid_count']} bulan dengan total {$rp}.",
                    'action'      => 'Lakukan penagihan dan kirim pengingat melalui WhatsApp.',
                ];
            } elseif ($r['pola'] === 'sering_telat') {
                $items[] = [
                    'type'        => 'sering_telat',
                    'priority'    => 3,
                    'tone'        => 'orange',
                    'icon'        => '⏱',
                    'title'       => 'Sering Terlambat',
                    'badge'       => ['Sering Telat', 'badge-yellow'],
                    'customer_id' => $c->id,
                    'amount'      => 0,
                    'text'        => "{$c->name} rata-rata terlambat {$r['avg_delay']} hari ({$r['late_count']} dari {$r['total']} tagihan).",
                    'action'      => 'Kirim reminder lebih awal dari biasanya (H-5) dan tawarkan metode bayar otomatis.',
                ];
            }
        }

        // Urutkan: tidak normal -> tunggakan -> sering telat, lalu nominal terbesar
        usort($items, fn ($a, $b) => [$a['priority'], -$a['amount']] <=> [$b['priority'], -$b['amount']]);

        return $items;
    }
}