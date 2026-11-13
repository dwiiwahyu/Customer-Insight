<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Collection;

class InsightService
{
    // [label, kelas badge] — dipakai badge di tabel detail pelanggan
    public const TIPE = [
        'upgrade'    => ['Upgrade Paket',       'badge-green'],
        'cross_sell' => ['Layanan Tambahan',    'badge-purple'],
        'churn'      => ['Risiko Churn',        'badge-red'],
        'usage_drop' => ['Penurunan Pemakaian', 'badge-orange'],
    ];

    public const RISIKO = [
        'tinggi' => ['Tinggi', 'badge-red'],
        'sedang' => ['Sedang', 'badge-yellow'],
        'rendah' => ['Rendah', 'badge-green'],
    ];

    /**
     * Jenis rekomendasi untuk halaman "Rekomendasi Lengkap" (urutan = urutan tabel).
     * priority = label prioritas tetap per jenis, tone = warna ikon.
     */
    public const REKOMENDASI = [
        'tunggakan' => [
            'title'    => 'Risiko Tunggakan',
            'desc'     => 'Pelanggan dengan tagihan yang belum dibayar',
            'priority' => 'tinggi',
            'tone'     => 'orange',
        ],
        'upgrade' => [
            'title'    => 'Peluang Upgrade Paket',
            'desc'     => 'Pelanggan dengan pemakaian tinggi yang cocok untuk paket lebih tinggi',
            'priority' => 'sedang',
            'tone'     => 'blue',
        ],
        'usage_drop' => [
            'title'    => 'Penurunan Penggunaan Layanan',
            'desc'     => 'Pelanggan aktif dengan pemakaian turun dalam 2 bulan terakhir',
            'priority' => 'sedang',
            'tone'     => 'red',
        ],
        'cross_sell' => [
            'title'    => 'Promosi Layanan Tambahan',
            'desc'     => 'Pelanggan yang berpotensi memakai layanan tambahan sesuai industri',
            'priority' => 'rendah',
            'tone'     => 'purple',
        ],
        'kepuasan' => [
            'title'    => 'Peningkatan Kepuasan Pelanggan',
            'desc'     => 'Pelanggan tanpa tunggakan yang sering terlambat membayar',
            'priority' => 'rendah',
            'tone'     => 'green',
        ],
        'churn' => [
            'title'    => 'Potensi Churn',
            'desc'     => 'Pelanggan berisiko churn tinggi (2 tagihan atau lebih belum dibayar, atau nonaktif)',
            'priority' => 'tinggi',
            'tone'     => 'yellow',
        ],
    ];

    /**
     * Pemakaian dianggap turun jika rasio kuota terpakai turun 2 bulan berturut-turut
     * dan selisih total minimal sekian (0.10 = 10 poin persentase).
     */
    public const USAGE_DROP_MIN = 0.10;

    public function __construct(private CustomerAnalyzer $analyzer)
    {
    }

    /**
     * Analisis semua pelanggan (relasi di-eager load, tanpa N+1 query).
     */
    public function analyzeAll(): Collection
    {
        return Customer::with(['payments', 'usages', 'services'])
            ->orderBy('id')
            ->get()
            ->map(fn (Customer $c) => $this->analyze($c));
    }

    /**
     * Peluang & rekomendasi untuk satu pelanggan.
     */
    public function analyze(Customer $customer): array
    {
        $payment = $this->analyzer->paymentBehavior($customer);
        $usage   = $this->analyzer->usageSummary($customer);
        $risk    = $this->analyzer->churnRisk($customer, $payment);

        $aktif  = $customer->status === 'aktif';
        $bersih = $payment['unpaid_count'] === 0;

        // Aturan: yang masih menunggak ditagih dulu, belum ditawari apa-apa
        $upgrade = $bersih ? $this->analyzer->upgradeInfo($customer, $usage) : null;
        $cross   = ($aktif && $bersih) ? $this->analyzer->crossSell($customer) : [];
        $drop    = $aktif ? $this->usageDrop($usage['usages']) : null;

        $rp       = 'Rp ' . number_format($payment['unpaid_amount'], 0, ',', '.');
        $types    = [];
        $recs     = [];
        $priority = 9; // makin kecil makin diprioritaskan

        // ---- Risiko churn ----
        if ($risk === 'tinggi') {
            $types[]  = 'churn';
            $priority = min($priority, 1);
            $recs[]   = ['type' => 'churn', 'text' => $payment['unpaid_count'] > 0
                ? "Prioritaskan penagihan {$rp} dan hubungi pelanggan sebelum layanan terdampak."
                : 'Pelanggan nonaktif. Lakukan pendekatan win-back agar kembali berlangganan.'];
        } elseif ($risk === 'sedang') {
            $types[]  = 'churn';
            $priority = min($priority, 3);
            $recs[]   = ['type' => 'churn', 'text' => $payment['unpaid_count'] > 0
                ? "Ada 1 tagihan belum dibayar ({$rp}). Lakukan penagihan dan follow up."
                : 'Sering terlambat pada tagihan terakhir. Jadwalkan follow up kepuasan pelanggan.'];
        }

        // ---- Upgrade paket ----
        if ($upgrade) {
            $types[]  = 'upgrade';
            $priority = min($priority, 2);
            $recs[]   = ['type' => 'upgrade', 'text' => "Tawarkan upgrade {$upgrade['from']} → {$upgrade['to']} Mbps (utilisasi {$usage['utilization']}%, estimasi peluang {$upgrade['probability']}%)."];
        }

        // ---- Layanan tambahan ----
        if ($cross) {
            $types[]  = 'cross_sell';
            $priority = min($priority, 4);
            $recs[]   = ['type' => 'cross_sell', 'text' => 'Tawarkan ' . implode(', ', $cross) . " sesuai industri {$customer->industry}."];
        }

        // ---- Penurunan pemakaian ----
        if ($drop) {
            $types[]  = 'usage_drop';
            $priority = min($priority, 5);
            $recs[]   = ['type' => 'usage_drop', 'text' => "Pemakaian turun dari {$drop['from']}% menjadi {$drop['to']}% kuota dalam 2 bulan terakhir. Hubungi pelanggan untuk memastikan layanan masih sesuai kebutuhan."];
        }

        [$riskLabel, $riskClass] = self::RISIKO[$risk];

        return [
            'customer'      => $customer,
            'risk'          => $risk,
            'risk_label'    => $riskLabel,
            'risk_class'    => $riskClass,
            'types'         => $types,
            'recs'          => $recs,
            'priority'      => $priority,
            'probability'   => $upgrade['probability'] ?? 0,
            'utilization'   => $usage['utilization'],
            'upgrade'       => $upgrade,
            'cross'         => $cross,
            'usage_drop'    => $drop,
            'unpaid_count'  => $payment['unpaid_count'],
            'unpaid_amount' => $payment['unpaid_amount'],
            'last_on_time'  => $payment['latest_status'] === 'tepat_waktu',
        ];
    }

    /**
     * Angka untuk kartu ringkasan & panel di halaman utama.
     */
    public function summary(Collection $rows): array
    {
        return [
            'upgrade'     => $rows->filter(fn ($r) => in_array('upgrade', $r['types']))->count(),
            'cross_sell'  => $rows->filter(fn ($r) => in_array('cross_sell', $r['types']))->count(),
            'usage_drop'  => $rows->filter(fn ($r) => in_array('usage_drop', $r['types']))->count(),
            'churn_high'  => $rows->where('risk', 'tinggi')->count(),
            'churn_mid'   => $rows->where('risk', 'sedang')->count(),
            'churn_total' => $rows->whereIn('risk', ['tinggi', 'sedang'])->count(),
            'positive'    => $rows->where('last_on_time', true)->count(),

            // Tagihan belum dibayar
            'unpaid_customers'   => $rows->filter(fn ($r) => $r['unpaid_count'] > 0)->count(),
            'unpaid_amount'      => $rows->sum('unpaid_amount'),
            'high_unpaid_amount' => $rows->where('risk', 'tinggi')->sum('unpaid_amount'),

            'avg_health' => $rows->isEmpty()
                ? 0
                : (int) round($rows->avg(fn ($r) => $r['customer']->health_score)),
        ];
    }

    /**
     * Apakah satu pelanggan (hasil analyze) masuk ke jenis rekomendasi tertentu?
     */
    public function inCategory(array $r, string $key): bool
    {
        return match ($key) {
            'tunggakan'  => $r['unpaid_count'] > 0,
            'upgrade'    => in_array('upgrade', $r['types'], true),
            'usage_drop' => in_array('usage_drop', $r['types'], true),
            'cross_sell' => in_array('cross_sell', $r['types'], true),
            'kepuasan'   => $r['risk'] === 'sedang' && $r['unpaid_count'] === 0,
            'churn'      => $r['risk'] === 'tinggi',
            default      => false,
        };
    }

    /**
     * Daftar jenis rekomendasi + jumlah pelanggan di tiap jenis (key = kode jenis).
     */
    public function recommendations(Collection $rows): array
    {
        $out = [];

        foreach (self::REKOMENDASI as $key => $info) {
            [$label, $class] = self::RISIKO[$info['priority']];

            $out[$key] = $info + [
                'count'          => $rows->filter(fn ($r) => $this->inCategory($r, $key))->count(),
                'priority_label' => $label,
                'priority_class' => $class,
            ];
        }

        return $out;
    }

    /**
     * Notifikasi dari hasil analisis: usage warning + opportunity.
     * (Notifikasi pembayaran ada di PaymentBehaviorService.)
     */
    public function notifications(Collection $rows): array
    {
        $items = [];

        foreach ($rows as $r) {
            $c = $r['customer'];

            if ($c->status === 'aktif' && $r['utilization'] >= 90) {
                $items[] = [
                    'tone'        => 'orange',
                    'title'       => 'Usage Warning',
                    'text'        => "{$c->name} sering mencapai limit penggunaan ({$r['utilization']}% dari kuota).",
                    'customer_id' => $c->id,
                    'tab'         => 'penggunaan',
                ];
            }

            if ($r['upgrade']) {
                $items[] = [
                    'tone'        => 'blue',
                    'title'       => 'Opportunity',
                    'text'        => "{$c->name} berpotensi upgrade ke {$r['upgrade']['to']} Mbps.",
                    'customer_id' => $c->id,
                    'tab'         => 'insight',
                ];
            }

            if ($r['cross']) {
                $items[] = [
                    'tone'        => 'blue',
                    'title'       => 'Opportunity',
                    'text'        => "{$c->name} berpotensi menggunakan layanan tambahan: " . implode(', ', $r['cross']) . '.',
                    'customer_id' => $c->id,
                    'tab'         => 'insight',
                ];
            }
        }

        return $items;
    }

    // ------------------------------------------------------------------ helper

    /**
     * Deteksi penurunan pemakaian: rasio download/kuota turun 2 bulan berturut-turut.
     * $usages = $usage['usages'] dari CustomerAnalyzer (sudah urut lama -> baru).
     * Mengembalikan ['from' => %, 'to' => %] atau null.
     */
    private function usageDrop(Collection $usages): ?array
    {
        $n = $usages->count();

        if ($n < 3) {
            return null;
        }

        $ratio = [];
        foreach ([$n - 3, $n - 2, $n - 1] as $i) {
            $quota = (float) $usages[$i]->quota_gb;
            if ($quota <= 0) {
                return null;
            }
            $ratio[] = (float) $usages[$i]->download_gb / $quota;
        }

        [$a, $b, $c] = $ratio;

        if ($b < $a && $c < $b && ($a - $c) >= self::USAGE_DROP_MIN) {
            return [
                'from' => (int) round($a * 100),
                'to'   => (int) round($c * 100),
            ];
        }

        return null;
    }
}