<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\InsightService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class InsightController extends Controller
{
    private const PER_PAGE = 10;

    // ---------------------------------------------------------------- HALAMAN 1: RINGKASAN
    public function index(InsightService $service)
    {
        $rows = $service->analyzeAll();

        // Rentang tanggal tagihan pada data pembayaran (hanya label info)
        $range   = Payment::selectRaw('MIN(invoice_date) as dari, MAX(invoice_date) as sampai')->first();
        $periode = ($range && $range->dari)
            ? Carbon::parse($range->dari)->locale('id')->translatedFormat('j M Y')
                . ' – ' . Carbon::parse($range->sampai)->locale('id')->translatedFormat('j M Y')
            : null;

        return view('insights.index', [
            'summary'         => $service->summary($rows),
            'recommendations' => $service->recommendations($rows),
            'periode'         => $periode,
        ]);
    }

    // ---------------------------------------------------------------- HALAMAN 2: REKOMENDASI LENGKAP
    public function full(InsightService $service)
    {
        $rows = $service->analyzeAll();

        return view('insights.full', [
            'recommendations' => $service->recommendations($rows),
            'tanggal'         => now()->locale('id')->translatedFormat('j F Y'),
        ]);
    }

    // ---------------------------------------------------------------- HALAMAN 3: DETAIL PER JENIS
    public function detail(Request $request, InsightService $service, string $kategori)
    {
        abort_unless(isset(InsightService::REKOMENDASI[$kategori]), 404);

        $filtered = $service->analyzeAll()
            ->filter(fn ($r) => $service->inCategory($r, $kategori))
            // Prioritas tertinggi dulu, lalu peluang upgrade terbesar
            ->sortBy([['priority', 'asc'], ['probability', 'desc']])
            ->values();

        // Paginasi manual (datanya hasil analisis, bukan query langsung)
        $page  = LengthAwarePaginator::resolveCurrentPage();
        $table = (new LengthAwarePaginator(
            $filtered->forPage($page, self::PER_PAGE)->values(),
            $filtered->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url()]
        ))->withQueryString();

        return view('insights.detail', [
            'info'        => InsightService::REKOMENDASI[$kategori],
            'rows'        => $table,
            'tipeOptions' => InsightService::TIPE,
        ]);
    }
}