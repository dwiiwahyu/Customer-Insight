<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerProduct;

class ServiceUsageAnalyzer
{
    // =====================================================
    // PILIHAN FILTER
    // =====================================================

    public function options(): array
    {
        return [
            'wilayah' => Customer::whereNotNull('wilayah')
                ->where('wilayah', '!=', '')
                ->distinct()
                ->orderBy('wilayah')
                ->pluck('wilayah')
                ->all(),

            'paket' => CustomerProduct::whereNotNull('product_group')
                ->where('product_group', '!=', '')
                ->distinct()
                ->orderBy('product_group')
                ->pluck('product_group')
                ->all(),
        ];
    }

    // =====================================================
    // QUERY PELANGGAN BERDASARKAN FILTER
    // =====================================================

    public function customerQuery(array $filters)
    {
        return Customer::query()

            // Filter wilayah
            ->when(
                $filters['wilayah'] !== 'Semua',
                function ($query) use ($filters) {
                    $query->where(
                        'wilayah',
                        $filters['wilayah']
                    );
                }
            )

            // Filter kategori produk
            ->when(
                $filters['paket'] !== 'Semua',
                function ($query) use ($filters) {
                    $query->whereHas(
                        'products',
                        function ($product) use ($filters) {
                            $product->where(
                                'product_group',
                                $filters['paket']
                            );
                        }
                    );
                }
            );
    }

    // =====================================================
    // QUERY PRODUK SESUAI FILTER
    // =====================================================

    private function productQuery(array $filters)
    {
        return CustomerProduct::query()

            ->whereIn(
                'customer_id',
                $this->customerQuery($filters)
                    ->select('customers.id')
            )

            ->when(
                $filters['paket'] !== 'Semua',
                function ($query) use ($filters) {
                    $query->where(
                        'product_group',
                        $filters['paket']
                    );
                }
            );
    }

    // =====================================================
    // ANALISIS UTAMA
    // =====================================================

    public function analyze(array $filters): array
    {
        $summary = $this->customerSummary($filters);

        return [
            'summary' => $summary,

            'products' => $this->productDistribution(
                $filters
            ),

            'status' => $this->statusDistribution(
                $summary
            ),

            'insight' => $this->buildInsight(
                $summary,
                $filters
            ),
        ];
    }

    // =====================================================
    // RINGKASAN JUMLAH PELANGGAN
    // =====================================================

    private function customerSummary(array $filters): array
    {
        $query = $this->customerQuery($filters);

        // Total pelanggan sesuai wilayah dan paket
        $total = (clone $query)->count();

        // Pelanggan aktif
        $active = (clone $query)
            ->where('status', 'aktif')
            ->count();

        // Pelanggan nonaktif
        $inactive = (clone $query)
            ->where('status', 'nonaktif')
            ->count();

        // Status selain aktif/nonaktif, jika ada
        $other = $total - $active - $inactive;

        // Persentase pelanggan aktif
        $activePercentage = $total > 0
            ? round(($active / $total) * 100, 1)
            : 0;

        // Persentase pelanggan nonaktif
        $inactivePercentage = $total > 0
            ? round(($inactive / $total) * 100, 1)
            : 0;

        // Jumlah layanan yang dimiliki pelanggan
        $totalProducts = $this->productQuery(
            $filters
        )->count();

        return [
            'total_customers' => $total,

            'active_customers' => $active,

            'inactive_customers' => $inactive,

            'other_customers' => $other,

            'active_percentage' => $activePercentage,

            'inactive_percentage' => $inactivePercentage,

            'total_products' => $totalProducts,
        ];
    }

    // =====================================================
    // DISTRIBUSI PRODUK LAYANAN
    // =====================================================

    private function productDistribution(
        array $filters
    ): array {
        $products = $this->productQuery($filters)

            ->whereNotNull('product_name')

            ->where('product_name', '!=', '')

            ->select('product_name')

            ->selectRaw(
                'COUNT(DISTINCT customer_id) as total'
            )

            ->groupBy('product_name')

            ->orderByDesc('total')

            ->orderBy('product_name')

            ->get();

        return [
            'labels' => $products
                ->pluck('product_name')
                ->all(),

            'values' => $products
                ->map(fn ($product) => (int) $product->total)
                ->all(),
        ];
    }

    // =====================================================
    // DISTRIBUSI STATUS PELANGGAN
    // =====================================================

    private function statusDistribution(
        array $summary
    ): array {
        $labels = [
            'Aktif',
            'Nonaktif',
        ];

        $values = [
            $summary['active_customers'],
            $summary['inactive_customers'],
        ];

        // Jika terdapat status lain di database
        if ($summary['other_customers'] > 0) {
            $labels[] = 'Lainnya';

            $values[] = $summary['other_customers'];
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    // =====================================================
    // DATA TABEL PELANGGAN
    // =====================================================

    public function customerDetails(array $filters)
    {
        return $this->customerQuery($filters)

            ->with([
                'products' => function ($query) use ($filters) {

                    $query->select([
                        'id',
                        'customer_id',
                        'product_group',
                        'product_name',
                        'abonemen',
                    ]);

                    // Hanya tampilkan produk sesuai filter
                    if ($filters['paket'] !== 'Semua') {
                        $query->where(
                            'product_group',
                            $filters['paket']
                        );
                    }
                }
            ]);
    }

    // =====================================================
    // ANALISIS DAN REKOMENDASI
    // =====================================================

    private function buildInsight(
        array $summary,
        array $filters
    ): array {
        $total = $summary['total_customers'];

        $active = $summary['active_customers'];

        $inactive = $summary['inactive_customers'];

        $other = $summary['other_customers'];

        $activePct = $summary['active_percentage'];

        $wilayah = $filters['wilayah'] === 'Semua'
            ? 'seluruh wilayah'
            : $filters['wilayah'];

        $paket = $filters['paket'] === 'Semua'
            ? 'semua kategori layanan'
            : $filters['paket'];

        // Tidak ada pelanggan sesuai filter
        if ($total === 0) {
            return [
                'analysis' =>
                    "Tidak ditemukan pelanggan di {$wilayah} "
                    . "dengan kategori {$paket}.",

                'recommendation' =>
                    'Coba pilih wilayah atau paket layanan lain.',

                'show_detail' => false,
            ];
        }

        // Analisis berdasarkan data pelanggan
        $analysis =
            "Terdapat {$total} pelanggan di {$wilayah} "
            . "dengan kategori layanan {$paket}. "
            . "Sebanyak {$active} pelanggan ({$activePct}%) "
            . "berstatus aktif dan {$inactive} pelanggan "
            . "berstatus nonaktif.";

        if ($other > 0) {
            $analysis .=
                " Terdapat {$other} pelanggan dengan status lainnya.";
        }

        // Ada pelanggan nonaktif
        if ($inactive > 0) {
            return [
                'analysis' => $analysis,

                'recommendation' =>
                    "Lakukan tindak lanjut kepada {$inactive} "
                    . "pelanggan nonaktif untuk mengetahui "
                    . "kendala layanan dan peluang aktivasi kembali.",

                'show_detail' => true,
            ];
        }

        // Ada pelanggan dengan status yang belum jelas
        if ($other > 0) {
            return [
                'analysis' => $analysis,

                'recommendation' =>
                    "Periksa status {$other} pelanggan "
                    . "yang belum memiliki status aktif "
                    . "atau nonaktif yang jelas.",

                'show_detail' => true,
            ];
        }

        // Semua pelanggan aktif
        return [
            'analysis' => $analysis,

            'recommendation' =>
                'Pertahankan kualitas layanan pelanggan aktif '
                . 'dan lakukan evaluasi kebutuhan layanan secara berkala.',

            'show_detail' => true,
        ];
    }
}
