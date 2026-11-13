<?php

namespace App\Http\Controllers;

use App\Services\ServiceUsageAnalyzer;
use Illuminate\Http\Request;

class ServiceUsageController extends Controller
{
    private const PER_PAGE = 10;

    // =====================================================
    // HALAMAN PENGGUNAAN LAYANAN
    // =====================================================

    public function index(
        Request $request,
        ServiceUsageAnalyzer $analyzer
    ) {
        // Pilihan dropdown
        $options = $analyzer->options();

        // Validasi filter
        $filters = $this->filters(
            $request,
            $options
        );

        // Statistik, grafik dan rekomendasi
        $data = $analyzer->analyze($filters);

        // Data tabel pelanggan sesuai filter
        $customers = $analyzer
            ->customerDetails($filters)
            ->orderBy('customers.id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('service-usage.index', [
            'filters' => $filters,

            'wilayahOptions' => $options['wilayah'],

            'paketOptions' => $options['paket'],

            'data' => $data,

            'customers' => $customers,
        ]);
    }

    // =====================================================
    // EXPORT DATA CSV
    // =====================================================

    public function export(
        Request $request,
        ServiceUsageAnalyzer $analyzer
    ) {
        $options = $analyzer->options();

        $filters = $this->filters(
            $request,
            $options
        );

        $filename = 'penggunaan-layanan-'
            . now()->format('Ymd-His')
            . '.csv';

        return response()->streamDownload(
            function () use ($analyzer, $filters) {

                $out = fopen('php://output', 'w');

                // UTF-8 BOM agar Excel membaca karakter
                // dengan benar
                fwrite($out, "\xEF\xBB\xBF");

                // Mencegah teks CSV dibaca sebagai formula
                $safe = function ($value) {
                    $text = (string) ($value ?? '');

                    return preg_match(
                        '/^[\s]*[=+\-@]/u',
                        $text
                    )
                        ? "'" . $text
                        : $text;
                };

                // Header CSV
                fputcsv($out, [
                    'No',
                    'NIPNAS',
                    'Nama Pelanggan',
                    'Wilayah',
                    'SA_STO',
                    'PRODUCT_GROUP',
                    'PRODUCT_NAME',
                    'Status Pelanggan',
                    'Jumlah Layanan',
                    'Total Abonemen',
                ]);

                $no = 1;

                // Ambil seluruh pelanggan sesuai filter,
                // bukan hanya pelanggan pada halaman tabel
                $customers = $analyzer
                    ->customerDetails($filters)
                    ->lazyById(200);

                foreach ($customers as $customer) {

                    $groups = $customer->products
                        ->pluck('product_group')
                        ->filter()
                        ->unique()
                        ->implode(', ');

                    $products = $customer->products
                        ->pluck('product_name')
                        ->filter()
                        ->unique()
                        ->implode(', ');

                    // Jumlah layanan pelanggan
                    $totalProducts = $customer
                        ->products
                        ->count();

                    // Total abonemen layanan sesuai filter
                    $abonemen = $customer
                        ->products
                        ->sum('abonemen');

                    fputcsv($out, [
                        $no++,

                        $safe($customer->customer_code),

                        $safe($customer->name),

                        $safe($customer->wilayah),

                        $safe($customer->sto),

                        $safe($groups),

                        $safe($products),

                        $safe(ucfirst(
                            $customer->status ?? ''
                        )),

                        $totalProducts,

                        $abonemen,
                    ]);
                }

                fclose($out);
            },

            $filename,

            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',
            ]
        );
    }

    // =====================================================
    // VALIDASI FILTER WILAYAH DAN PAKET
    // =====================================================

    private function filters(
        Request $request,
        array $options
    ): array {
        // Filter wilayah
        $wilayah = $request->query(
            'wilayah',
            'Semua'
        );

        if (
            !is_string($wilayah) ||
            (
                $wilayah !== 'Semua' &&
                !in_array(
                    $wilayah,
                    $options['wilayah'],
                    true
                )
            )
        ) {
            $wilayah = 'Semua';
        }

        // Filter paket
        $paket = $request->query(
            'paket',
            'Semua'
        );

        if (
            !is_string($paket) ||
            (
                $paket !== 'Semua' &&
                !in_array(
                    $paket,
                    $options['paket'],
                    true
                )
            )
        ) {
            $paket = 'Semua';
        }

        return [
            'wilayah' => $wilayah,

            'paket' => $paket,
        ];
    }
}
