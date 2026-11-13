<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\CustomerAnalyzer;
use App\Services\PaymentBehaviorService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentBehaviorController extends Controller
{
    private const PER_PAGE = 10;

    public function index(
        Request $request,
        PaymentBehaviorService $service
    ) {
        // Analisis seluruh pelanggan
        $rows = $service->analyzeAll();

        // Ringkasan pembayaran
        $summary = $service->summary($rows);

        // Filter tabel berdasarkan pola
        $pola = $request->query('pola');

        $filtered = (
            is_string($pola) &&
            isset(PaymentBehaviorService::POLA[$pola])
        )
            ? $rows->where('pola', $pola)
            : $rows;

        // Paginasi manual
        $page = LengthAwarePaginator::resolveCurrentPage();

        $table = (new LengthAwarePaginator(
            $filtered
                ->forPage($page, self::PER_PAGE)
                ->values(),

            $filtered->count(),

            self::PER_PAGE,

            $page,

            [
                'path' => $request->url()
            ]

        ))->withQueryString();

        // Rata-rata keterlambatan per bulan
        $trend = Payment::whereNotNull('paid_date')

            ->get([
                'invoice_date',
                'delay_days'
            ])

            ->groupBy(
                fn ($p) => $p->invoice_date->format('Y-m')
            )

            ->sortKeys()

            ->map(fn ($group, $key) => [

                'm' => (int) substr($key, 5, 2),

                'avg_delay' => round(
                    $group->avg('delay_days'),
                    1
                ),

            ])

            ->take(-6)

            ->values();

        // Data grafik
        $charts = [

            'pola' => [

                'labels' => collect(
                    PaymentBehaviorService::POLA
                )
                    ->map(fn ($p) => $p[0])
                    ->values()
                    ->all(),

                'values' => array_values(
                    $summary['pola_counts']
                ),

            ],

            'delay' => [

                'labels' => $trend

                    ->map(
                        fn ($t) =>
                            CustomerAnalyzer::BULAN[$t['m'] - 1]
                    )

                    ->all(),

                'values' => $trend
                    ->pluck('avg_delay')
                    ->all(),

            ],

        ];

        // Tampilkan halaman perilaku pembayaran
        return view('payments.behavior', [

            'summary' => $summary,

            'rows' => $table,

            'charts' => $charts,

            'polaFilter' => $pola,

            'polaOptions' => PaymentBehaviorService::POLA,

        ]);
    }
}
