<?php

namespace App\Services;

use App\Models\Customer;

class CustomerAnalyzer
{
    public const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    // Tangga kecepatan (Mbps) untuk saran upgrade
    private const SPEED_LADDER = [2, 5, 10, 20, 30, 50, 100, 200, 300, 500, 1000, 2000];

    // Layanan yang kecepatannya bisa di-upgrade (dicocokkan ke PRODUCT_GROUP + PRODUCT_NAME)
    private const UPGRADEABLE = ['connectivity', 'network', 'internet'];

    // Layanan tambahan yang relevan per industri (nama industri ditulis huruf kecil)
    private const CROSS_SELL = [
        'sekolah'            => ['WMS', 'CCTV'],
        'universitas'        => ['WMS', 'CCTV', 'VPN IP'],
        'bimbingan belajar'  => ['WMS', 'CCTV'],
        'warnet'             => ['CCTV', 'POS/PAYMENT SYSTEM'],
        'percetakan'         => ['VPN IP', 'CCTV'],
        'agribisnis'         => ['CCTV', 'VPN IP'],
        'perkebunan'         => ['CCTV', 'VPN IP'],
        'peternakan'         => ['CCTV'],
        'restoran'           => ['POS/PAYMENT SYSTEM', 'WMS', 'CCTV'],
        'kafe'               => ['POS/PAYMENT SYSTEM', 'WMS', 'CCTV'],
        'hotel'              => ['WMS', 'CCTV', 'SIP TRUNK'],
        'villa dan homestay' => ['WMS', 'CCTV'],
        'klinik'             => ['CCTV', 'SIP TRUNK'],
        'apotek'             => ['POS/PAYMENT SYSTEM', 'CCTV'],
        'rumah sakit'        => ['CCTV', 'SIP TRUNK', 'WMS'],
        'toko retail'        => ['POS/PAYMENT SYSTEM', 'CCTV'],
        'supermarket'        => ['POS/PAYMENT SYSTEM', 'CCTV', 'VPN IP'],
        'pabrik'             => ['VPN IP', 'CCTV'],
        'bengkel'            => ['POS/PAYMENT SYSTEM', 'CCTV'],
        'jasa logistik'      => ['VPN IP', 'CCTV'],
        'koperasi'           => ['POS/PAYMENT SYSTEM', 'VPN IP'],
        'konstruksi'         => ['VPN IP', 'CCTV'],
        'perumahan'          => ['CCTV', 'WMS'],
        // nama industri generik (kalau data asli memakainya)
        'f&b'                => ['POS/PAYMENT SYSTEM', 'WMS', 'CCTV'],
        'retail'             => ['POS/PAYMENT SYSTEM', 'CCTV'],
        'pendidikan'         => ['WMS', 'CCTV'],
        'perhotelan'         => ['WMS', 'CCTV', 'SIP TRUNK'],
        'kesehatan'          => ['CCTV', 'SIP TRUNK'],
    ];

    // ================================================================ PEMBAYARAN
    /**
     * Ringkasan perilaku pembayaran satu pelanggan.
     */
    public function paymentBehavior(Customer $customer): array
    {
        $payments = $customer->relationLoaded('payments')
            ? $customer->payments->sortBy(fn ($p) => $p->invoice_date->timestamp)->values()
            : $customer->payments()->orderBy('invoice_date')->get();

        $late   = $payments->where('status', 'terlambat');
        $unpaid = $payments->where('status', 'tunggakan');
        $paid   = $payments->where('status', '!=', 'tunggakan');

        // Tanggal bayar yang paling "biasa" (median hari dalam bulan)
        $paidDays   = $paid->map(fn ($p) => $p->paid_date->day)->sort()->values();
        $typicalDay = $paidDays->isEmpty() ? null : $paidDays[intdiv($paidDays->count(), 2)];

        return [
            'payments'      => $payments, // urut lama -> baru
            'latest_status' => $payments->last()?->status,
            'late_count'    => $late->count(),
            'unpaid_count'  => $unpaid->count(),
            'unpaid_amount' => (int) $unpaid->sum('amount'),
            // rata-rata hanya dari tagihan yang terlambat
            'avg_delay'     => $late->isEmpty() ? 0 : (int) round($late->avg('delay_days')),
            'typical_day'   => $typicalDay,
            // berapa dari 3 tagihan terakhir yang terlambat/menunggak
            'last3_late'    => $payments->slice(-3)->whereIn('status', ['terlambat', 'tunggakan'])->count(),
            'distribution'  => [
                'tepat_waktu' => $payments->where('status', 'tepat_waktu')->count(),
                'terlambat'   => $late->count(),
                'tunggakan'   => $unpaid->count(),
            ],
        ];
    }

    /**
     * Pola pembayaran: rutin | kadang_telat | sering_telat | menunggak
     * (aturan sama dengan halaman Perilaku Pembayaran).
     */
    public function paymentPattern(array $payment): string
    {
        $paid      = $payment['payments']->count() - $payment['unpaid_count'];
        $lateRatio = $paid > 0 ? $payment['late_count'] / $paid : 0;

        return match (true) {
            $payment['unpaid_count'] > 0 => 'menunggak',
            $lateRatio >= 0.7            => 'sering_telat',
            $lateRatio >= 0.3            => 'kadang_telat',
            default                      => 'rutin',
        };
    }

    /**
     * Tren keterlambatan: rata-rata telat 2 bulan terakhir vs bulan sebelumnya.
     * Hasil: membaik | stabil | memburuk
     */
    public function paymentTrend(array $payment): string
    {
        $delays = $payment['payments']
            ->where('status', '!=', 'tunggakan')
            ->pluck('delay_days')
            ->values();

        if ($delays->count() < 4) {
            return 'stabil';
        }

        $recent = $delays->slice(-2)->avg();
        $before = $delays->slice(0, $delays->count() - 2)->avg();
        $diff   = $recent - $before;

        return $diff >= 1.5 ? 'memburuk' : ($diff <= -1.5 ? 'membaik' : 'stabil');
    }

    /**
     * Risiko churn: rendah | sedang | tinggi.
     */
    public function churnRisk(Customer $customer, array $payment): string
    {
        if ($payment['unpaid_count'] >= 2 || $customer->status === 'nonaktif') {
            return 'tinggi';
        }

        if ($payment['unpaid_count'] === 1 || $payment['last3_late'] >= 2) {
            return 'sedang';
        }

        return 'rendah';
    }

    // ================================================================ PEMAKAIAN
    /**
     * Ringkasan pemakaian satu pelanggan (dipakai halaman Insight & Rekomendasi).
     */
    public function usageSummary(Customer $customer): array
    {
        $usages = $customer->relationLoaded('usages')
            ? $customer->usages->sortBy(fn ($u) => $u->period->timestamp)->values()
            : $customer->usages()->orderBy('period')->get();

        $latest = $usages->last();

        $utilization = ($latest && $latest->quota_gb > 0)
            ? (int) round($latest->download_gb / $latest->quota_gb * 100)
            : 0;

        // Tren: rata-rata download 3 bulan terakhir vs 3 bulan sebelumnya
        $recent = $usages->slice(-3)->avg('download_gb');
        $before = $usages->slice(-6, 3)->avg('download_gb');

        $trend = 'stabil';
        if ($recent && $before) {
            $change = ($recent - $before) / $before;
            $trend  = $change > 0.1 ? 'meningkat' : ($change < -0.1 ? 'menurun' : 'stabil');
        }

        return [
            'usages'      => $usages, // urut lama -> baru
            'latest'      => $latest,
            'utilization' => $utilization,
            'total_gb'    => $latest ? $latest->download_gb + $latest->upload_gb : 0,
            'trend'       => $trend,
        ];
    }

    // ================================================================ PELUANG
    /**
     * Rencana upgrade: layanan konektivitas pertama yang masih punya tingkat kecepatan di atasnya.
     * Kecepatan dibaca dari CIRCUIT_SPEED (contoh "100MBPS").
     */
    public function upgradePlan(Customer $customer): ?array
    {
        foreach ($customer->products as $p) {
            $label = strtolower(($p->product_group ?? '') . ' ' . ($p->product_name ?? ''));

            $bisa = false;
            foreach (self::UPGRADEABLE as $kata) {
                if (str_contains($label, $kata)) {
                    $bisa = true;
                    break;
                }
            }

            $speed = $this->speedOf($p->circuit_speed);

            if (! $bisa || ! $speed) {
                continue;
            }

            foreach (self::SPEED_LADDER as $tingkat) {
                if ($tingkat > $speed) {
                    return [
                        'product' => $p->product_name ?: $p->product_group,
                        'from'    => $speed,
                        'to'      => $tingkat,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Kandidat upgrade berdasarkan pemakaian (dipakai halaman Insight & Rekomendasi).
     */
    public function upgradeInfo(Customer $customer, array $usage): ?array
    {
        if ($customer->status !== 'aktif' || $usage['utilization'] < 75) {
            return null;
        }

        $plan = $this->upgradePlan($customer);

        if (! $plan) {
            return null;
        }

        return [
            'from'        => $plan['from'],
            'to'          => $plan['to'],
            'probability' => min(95, $usage['utilization']), // heuristik sederhana
        ];
    }

    /**
     * Layanan tambahan yang relevan dengan industri pelanggan dan belum dimilikinya.
     */
    public function crossSell(Customer $customer): array
    {
        $list = self::CROSS_SELL[strtolower(trim((string) $customer->industry))] ?? [];

        // Layanan/produk yang sudah dimiliki (dari tabel layanan maupun data master)
        $owned = $customer->services->pluck('service')
            ->merge($customer->products->pluck('product_name'))
            ->merge($customer->products->pluck('product_group'))
            ->filter()
            ->map(fn ($s) => strtolower(trim($s)))
            ->unique();

        return array_values(array_filter($list, function ($item) use ($owned) {
            $item = strtolower($item);

            foreach ($owned as $name) {
                if (str_contains($name, $item) || str_starts_with($item, $name)) {
                    return false;
                }
            }

            return true;
        }));
    }

    // ================================================================ TAB OVERVIEW
    /**
     * 3 kartu ringkasan di tab Overview (semuanya dari pola pembayaran).
     */
    public function overview(Customer $customer, array $payment): array
    {
        if ($payment['payments']->isEmpty()) {
            $note = 'Belum ada riwayat pembayaran';

            return [
                ['label' => 'Pembayaran',      'icon' => 'Rp', 'value' => 'N/A', 'note' => $note, 'tone' => 'gray'],
                ['label' => 'Risiko Churn',    'icon' => '!',  'value' => 'N/A', 'note' => $note, 'tone' => 'gray'],
                ['label' => 'Peluang Layanan', 'icon' => '+',  'value' => 'N/A', 'note' => $note, 'tone' => 'gray'],
            ];
        }

        $pola  = $this->paymentPattern($payment);
        $risk  = $this->churnRisk($customer, $payment);
        $aktif = $customer->status === 'aktif';

        $pay = match ($pola) {
            'menunggak'    => ['value' => 'CRITICAL', 'note' => $payment['unpaid_count'] . ' tagihan belum dibayar', 'tone' => 'red'],
            'sering_telat' => ['value' => 'WARNING',  'note' => 'Sering terlambat membayar',  'tone' => 'orange'],
            'kadang_telat' => ['value' => 'FAIR',     'note' => 'Kadang terlambat membayar',  'tone' => 'orange'],
            default        => ['value' => 'GOOD',     'note' => 'Tidak ada tunggakan',        'tone' => 'green'],
        };

        $churn = match ($risk) {
            'tinggi' => ['value' => 'TINGGI', 'note' => 'Segera ditangani',        'tone' => 'red'],
            'sedang' => ['value' => 'SEDANG', 'note' => 'Perlu follow up',         'tone' => 'orange'],
            default  => ['value' => 'RENDAH', 'note' => 'Pola pembayaran stabil',  'tone' => 'green'],
        };

        $upgrade = ($aktif && $pola === 'rutin') ? $this->upgradePlan($customer) : null;
        $cross   = ($aktif && in_array($pola, ['rutin', 'kadang_telat'], true)) ? $this->crossSell($customer) : [];

        if ($upgrade) {
            $opp = ['value' => 'AVAILABLE', 'note' => 'Potensi upgrade paket', 'tone' => 'orange'];
        } elseif ($cross) {
            $opp = ['value' => 'AVAILABLE', 'note' => 'Potensi layanan tambahan', 'tone' => 'orange'];
        } elseif (in_array($pola, ['menunggak', 'sering_telat'], true)) {
            $opp = ['value' => 'DITUNDA', 'note' => 'Tunggu pembayaran membaik', 'tone' => 'gray'];
        } else {
            $opp = ['value' => 'NONE', 'note' => 'Belum ada peluang', 'tone' => 'gray'];
        }

        return [
            ['label' => 'Pembayaran',      'icon' => 'Rp'] + $pay,
            ['label' => 'Risiko Churn',    'icon' => '!'] + $churn,
            ['label' => 'Peluang Layanan', 'icon' => '+'] + $opp,
        ];
    }

    // ================================================================ TAB INSIGHT
    /**
     * Isi tab Insight: 3 kartu, ringkasan, dan rekomendasi tindakan.
     * Semuanya diturunkan dari pola pembayaran + data master pelanggan.
     */
    public function insights(Customer $customer, array $payment): array
    {
        if ($payment['payments']->isEmpty()) {
            return $this->emptyInsights();
        }

        $pola  = $this->paymentPattern($payment);
        $trend = $this->paymentTrend($payment);
        $risk  = $this->churnRisk($customer, $payment);
        $aktif = $customer->status === 'aktif';

        $total  = $payment['payments']->count();
        $unpaid = $payment['unpaid_count'];
        $paid   = $total - $unpaid;
        $late   = $payment['late_count'];
        $ontime = $payment['distribution']['tepat_waktu'];
        $avg    = $payment['avg_delay'];
        $last3  = $payment['last3_late'];
        $rp     = 'Rp ' . number_format($payment['unpaid_amount'], 0, ',', '.');
        $oldest = (int) $payment['payments']->where('status', 'tunggakan')->max('delay_days');

        $upgrade  = ($aktif && $pola === 'rutin') ? $this->upgradePlan($customer) : null;
        $chance   = $upgrade ? $this->upgradeChance($payment, $trend) : 0;
        $cross    = ($aktif && in_array($pola, ['rutin', 'kadang_telat'], true)) ? $this->crossSell($customer) : [];
        $list     = implode(', ', $cross);
        $industri = $customer->industry ?: 'pelanggan';

        $trendText = match ($trend) {
            'memburuk' => 'Keterlambatan cenderung meningkat dalam beberapa bulan terakhir.',
            'membaik'  => 'Pola pembayaran mulai membaik dalam beberapa bulan terakhir.',
            default    => '',
        };

        // ---------------------------------------------------------- Kartu 1: upgrade paket
        if (! $aktif) {
            $card1 = $this->card('Info', 'badge-gray', 'Tidak Ada Peluang', 'Pelanggan berstatus nonaktif, belum ada peluang upgrade paket.');
        } elseif ($upgrade) {
            $card1 = $this->card('Peluang', 'badge-green', 'Peluang Upgrade Paket',
                "Pelanggan membayar tepat waktu ({$ontime} dari {$total} tagihan) dengan paket {$upgrade['product']} {$upgrade['from']} Mbps. Berpotensi upgrade ke {$upgrade['to']} Mbps.");
        } elseif ($unpaid > 0) {
            $card1 = $this->card('Ditunda', 'badge-gray', 'Upgrade Belum Disarankan',
                "Selesaikan tunggakan {$rp} terlebih dahulu sebelum menawarkan upgrade paket.");
        } elseif ($pola !== 'rutin') {
            $card1 = $this->card('Ditunda', 'badge-gray', 'Upgrade Belum Disarankan',
                "Pola pembayaran belum stabil (terlambat {$late} dari {$paid} tagihan). Perbaiki pembayaran dulu sebelum menawarkan upgrade paket.");
        } else {
            $card1 = $this->card('Info', 'badge-gray', 'Paket Sudah Sesuai',
                'Belum ada tingkat paket di atasnya yang bisa ditawarkan untuk layanan pelanggan saat ini.');
        }

        // ---------------------------------------------------------- Kartu 2: risiko churn
        if ($risk === 'tinggi') {
            $card2 = $this->card('Perlu Perhatian', 'badge-red', 'Resiko Churn Tinggi',
                $unpaid > 0
                    ? "{$unpaid} tagihan belum dibayar ({$rp}), tunggakan terlama {$oldest} hari. Segera hubungi pelanggan dan lakukan penagihan."
                    : 'Pelanggan berstatus nonaktif. Perlu pendekatan agar kembali berlangganan.');
        } elseif ($risk === 'sedang') {
            $base = $unpaid === 1
                ? "1 tagihan belum dibayar ({$rp})."
                : "Terlambat pada {$last3} dari 3 tagihan terakhir (rata-rata {$avg} hari).";

            $card2 = $this->card('Perlu Perhatian', 'badge-yellow', 'Resiko Churn Sedang',
                implode(' ', array_filter([$base, $trendText, 'Perlu follow up untuk memastikan kepuasan pelanggan.'])));
        } else {
            $base = $pola === 'rutin'
                ? "Pembayaran stabil: {$ontime} dari {$total} tagihan tepat waktu" . ($avg > 0 ? ", rata-rata keterlambatan {$avg} hari." : '.')
                : "Risiko rendah saat ini, namun pelanggan kadang terlambat ({$late} dari {$paid} tagihan). Tetap dipantau.";

            $card2 = $this->card('Aman', 'badge-green', 'Resiko Churn Rendah',
                implode(' ', array_filter([$base, $trendText])));
        }

        // ---------------------------------------------------------- Kartu 3: promosi relevan
        if ($unpaid > 0) {
            $card3 = $this->card('Rekomendasi', 'badge-purple', 'Promosi Ditunda',
                'Fokus penagihan lebih dulu. Promo baru ditawarkan setelah tunggakan terselesaikan.');
        } elseif ($pola === 'sering_telat') {
            $card3 = $this->card('Rekomendasi', 'badge-purple', 'Promosi yang Relevan',
                'Karena sering terlambat, tawarkan promo pembayaran otomatis (autodebet) atau diskon untuk pembayaran tepat waktu.');
        } elseif ($cross) {
            $card3 = $this->card('Rekomendasi', 'badge-purple', 'Promosi yang Relevan',
                "Berdasarkan industri {$industri}, pelanggan berpotensi menggunakan: {$list}.");
        } else {
            $card3 = $this->card('Info', 'badge-gray', 'Promosi yang Relevan',
                'Belum ada promo layanan tambahan yang relevan untuk pelanggan ini.');
        }

        // ---------------------------------------------------------- Ringkasan insight
        $polaText = match ($pola) {
            'rutin'        => "Pelanggan membayar secara rutin: {$ontime} dari {$total} tagihan tepat waktu.",
            'kadang_telat' => "Pelanggan kadang terlambat membayar: terlambat pada {$late} dari {$paid} tagihan yang sudah dibayar.",
            'sering_telat' => "Pelanggan sering terlambat membayar: terlambat pada {$late} dari {$paid} tagihan yang sudah dibayar.",
            default        => "Pelanggan memiliki {$unpaid} tagihan belum dibayar dengan total {$rp}.",
        };

        $timingText = match (true) {
            $unpaid > 0 => "Tunggakan terlama sudah berjalan {$oldest} hari sejak tanggal tagihan.",
            $avg > 0    => "Tidak ada tunggakan pembayaran, namun perlu dipantau karena rata-rata keterlambatan {$avg} hari.",
            default     => 'Tidak ada tunggakan dan pelanggan belum pernah terlambat membayar.',
        };

        if ($trendText !== '') {
            $timingText .= ' ' . $trendText;
        }

        $oppText = match (true) {
            (bool) $upgrade
                => "Peluang upgrade paket ke {$upgrade['to']} Mbps " . ($chance >= 70 ? 'cukup tinggi' : 'ada') . " (estimasi {$chance}%).",
            (bool) $cross
                => "Ada peluang layanan tambahan: {$list}.",
            $unpaid > 0 || $pola === 'sering_telat'
                => 'Peluang penjualan ditunda sampai pola pembayaran pelanggan membaik.',
            default
                => 'Belum ada indikasi peluang layanan tambahan.',
        };

        $recText = match (true) {
            ! $aktif                 => 'Rekomendasi: Lakukan pendekatan win-back agar pelanggan kembali berlangganan.',
            $risk === 'tinggi'       => 'Rekomendasi: Prioritaskan penagihan dan hubungi pelanggan sebelum layanan terdampak.',
            $pola === 'sering_telat' => 'Rekomendasi: Kirim reminder lebih awal dan tawarkan pembayaran otomatis agar tidak terlewat.',
            (bool) $upgrade          => 'Rekomendasi: Tawarkan promo upgrade paket dan pastikan kepuasan pelanggan tetap terjaga.',
            (bool) $cross            => 'Rekomendasi: Tawarkan layanan tambahan yang relevan dengan industri pelanggan.',
            default                  => 'Rekomendasi: Pertahankan kualitas layanan dan pantau pembayaran bulan berikutnya.',
        };

        // ---------------------------------------------------------- Rekomendasi tindakan: [bagian merah, sisa kalimat]
        $actions = [];

        if ($unpaid > 0) {
            $actions[] = ['Hubungi pelanggan', ' untuk menanyakan kendala dan menagih tunggakan.'];
        }

        if ($upgrade) {
            $actions[] = ['Hubungi pelanggan', " untuk menawarkan upgrade paket ke {$upgrade['to']} Mbps."];
        }

        if ($cross) {
            $actions[] = ['Kirimkan promo', " eksklusif ({$list}) melalui WhatsApp."];
        }

        if ($aktif && $unpaid === 0 && in_array($pola, ['kadang_telat', 'sering_telat'], true)) {
            $day = $payment['typical_day'];

            $actions[] = ['Kirim reminder', $day && $day > 1
                ? " pembayaran sebelum tanggal {$day} setiap bulan."
                : ' pembayaran 2 hari sebelum tanggal tagihan.'];
        }

        if ($aktif && $pola === 'sering_telat') {
            $actions[] = ['Tawarkan pembayaran otomatis', ' (autodebet) agar pembayaran tidak terlewat.'];
        }

        $actions[] = ['Pantau pembayaran', ' di bulan selanjutnya.'];

        if ($risk !== 'rendah' || $upgrade) {
            $actions[] = ['Jadwalkan follow up', ' kepuasan pelanggan.'];
        }

        return [
            'cards'   => [$card1, $card2, $card3],
            'summary' => [
                ['icon' => 'chart', 'tone' => 'green',  'text' => $polaText],
                ['icon' => 'clock', 'tone' => 'orange', 'text' => $timingText],
                ['icon' => 'star',  'tone' => 'blue',   'text' => $oppText],
                ['icon' => 'gift',  'tone' => 'purple', 'text' => $recText],
            ],
            'actions' => $actions,
        ];
    }

    // ================================================================ HELPER
    private function card(string $tag, string $class, string $title, string $text): array
    {
        return ['tag' => $tag, 'tag_class' => $class, 'title' => $title, 'text' => $text];
    }

    // Isi tab Insight kalau pelanggan belum punya riwayat pembayaran
    private function emptyInsights(): array
    {
        $text = 'Insight muncul setelah riwayat pembayaran pelanggan ini diupload.';

        return [
            'cards' => [
                $this->card('Info', 'badge-gray', 'Peluang Upgrade Paket', $text),
                $this->card('Info', 'badge-gray', 'Resiko Churn', $text),
                $this->card('Info', 'badge-gray', 'Promosi yang Relevan', $text),
            ],
            'summary' => [
                ['icon' => 'chart', 'tone' => 'green', 'text' => 'Belum ada riwayat pembayaran untuk dianalisis.'],
            ],
            'actions' => [
                ['Upload riwayat pembayaran', ' pelanggan ini lewat Tambah pelanggan → Upload CSV.'],
            ],
        ];
    }

    // "100MBPS" -> 100, "1GBPS" -> 1000
    private function speedOf(?string $value): ?int
    {
        if (! preg_match('/(\d+)/', (string) $value, $m)) {
            return null;
        }

        $speed = (int) $m[1];

        return stripos((string) $value, 'gbps') !== false ? $speed * 1000 : $speed;
    }

    // Estimasi peluang upgrade (heuristik): makin banyak tagihan tepat waktu makin tinggi
    private function upgradeChance(array $payment, string $trend): int
    {
        $total  = max(1, $payment['payments']->count());
        $ontime = $payment['distribution']['tepat_waktu'];

        $score = 35 + (int) round($ontime / $total * 50)
               + ($trend === 'membaik' ? 10 : ($trend === 'memburuk' ? -10 : 0));

        return max(5, min(95, $score));
    }
}