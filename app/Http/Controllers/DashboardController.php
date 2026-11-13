<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Usage;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public function index()
    {
        // Bulan acuan = bulan tagihan terbaru yang ada di database
        $latestInvoice = Payment::max('invoice_date');
        $ref  = $latestInvoice ? Carbon::parse($latestInvoice)->startOfMonth() : now()->startOfMonth();
        $prev = $ref->copy()->subMonth();

        $statusNow  = $this->hitungPerStatus($ref);
        $statusPrev = $this->hitungPerStatus($prev);

        // Pelanggan "bulan lalu" = yang sudah terpasang sebelum bulan acuan
        $totalNow  = Customer::count();
        $totalPrev = Customer::whereDate('install_date', '<', $ref)->count();
        $aktifNow  = Customer::where('status', 'aktif')->count();
        $aktifPrev = Customer::where('status', 'aktif')->whereDate('install_date', '<', $ref)->count();

        // Peluang layanan: pemakaian >= 75% kuota pada bulan pemakaian terbaru
        $usageMax = Usage::max('period');
        $usageRef = $usageMax ? Carbon::parse($usageMax)->startOfMonth() : $ref;
        $peluangNow  = $this->hitungPeluang($usageRef);
        $peluangPrev = $this->hitungPeluang($usageRef->copy()->subMonth());

        $cards = [
            ['label' => 'Total Pelanggan',     'value' => $totalNow,                   'change' => $this->persen($totalNow, $totalPrev),                              'higher_is_bad' => false],
            ['label' => 'Pelanggan Aktif',     'value' => $aktifNow,                   'change' => $this->persen($aktifNow, $aktifPrev),                              'higher_is_bad' => false],
            ['label' => 'Terlambat Membayar',  'value' => $statusNow['terlambat'],     'change' => $this->persen($statusNow['terlambat'], $statusPrev['terlambat']),  'higher_is_bad' => true],
            ['label' => 'Tunggakan',           'value' => $statusNow['tunggakan'],     'change' => $this->persen($statusNow['tunggakan'], $statusPrev['tunggakan']),  'higher_is_bad' => true],
            ['label' => 'Peluang Layanan',     'value' => $peluangNow,                 'change' => $this->persen($peluangNow, $peluangPrev),                          'higher_is_bad' => false],
        ];

        // Tren 6 bulan terakhir (sampai bulan acuan)
        $trend = ['labels' => [], 'tepat_waktu' => [], 'terlambat' => [], 'tunggakan' => []];
        for ($i = 5; $i >= 0; $i--) {
            $bulan  = $ref->copy()->subMonths($i);
            $status = $this->hitungPerStatus($bulan);

            $trend['labels'][]      = self::BULAN[$bulan->month - 1];
            $trend['tepat_waktu'][] = $status['tepat_waktu'];
            $trend['terlambat'][]   = $status['terlambat'];
            $trend['tunggakan'][]   = $status['tunggakan'];
        }

        // Donut: komposisi status bulan acuan
        $donut = [
            'labels' => ['Tepat Waktu', 'Terlambat', 'Tunggakan'],
            'values' => [$statusNow['tepat_waktu'], $statusNow['terlambat'], $statusNow['tunggakan']],
        ];

        return view('dashboard', [
            'cards'         => $cards,
            'trend'         => $trend,
            'donut'         => $donut,
            'jumlahTelat'   => $statusNow['terlambat'],
            'jumlahPeluang' => $peluangNow,
        ]);
    }

    // Jumlah tagihan per status pada satu bulan
    private function hitungPerStatus(Carbon $bulan): array
    {
        $rows = Payment::whereYear('invoice_date', $bulan->year)
            ->whereMonth('invoice_date', $bulan->month)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'tepat_waktu' => (int) ($rows['tepat_waktu'] ?? 0),
            'terlambat'   => (int) ($rows['terlambat'] ?? 0),
            'tunggakan'   => (int) ($rows['tunggakan'] ?? 0),
        ];
    }

    // Pelanggan aktif dengan pemakaian >= 75% kuota pada satu bulan
    private function hitungPeluang(Carbon $bulan): int
    {
        return Usage::whereDate('period', $bulan->toDateString())
            ->whereRaw('download_gb >= quota_gb * 0.75')
            ->whereHas('customer', fn ($q) => $q->where('status', 'aktif'))
            ->count();
    }

    // Persentase perubahan sekarang vs sebelumnya
    private function persen(int|float $now, int|float $before): float
    {
        if ($before == 0) {
            return $now > 0 ? 100.0 : 0.0;
        }

        return round((($now - $before) / $before) * 100, 1);
    }
}