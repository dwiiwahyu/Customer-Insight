<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerProduct;
use App\Models\Payment;
use App\Services\CustomerAnalyzer;
use App\Services\DummyActivityGenerator;
use App\Services\MasterImporter;
use App\Services\PaymentImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        // Muat produk pelanggan sekaligus (untuk kolom PRODUCT_GROUP) dan
        // ambil status tagihan terbaru tiap pelanggan sebagai kolom tambahan
        $query = Customer::query()
            ->with('products:id,customer_id,product_group')
            ->addSelect([
                'latest_payment_status' => Payment::select('status')
                    ->whereColumn('customer_id', 'customers.id')
                    ->orderByDesc('invoice_date')
                    ->limit(1),
            ]);

        // Pencarian: nama, NIPNAS, nomor internet/akun, no kontrak, no HP
        if ($q = trim((string) $request->q)) {
            $kode = ltrim($q, '#');

            $query->where(function ($w) use ($q, $kode) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('customer_code', 'like', "%{$kode}%")
                  ->orWhere('internet_number', 'like', "%{$q}%")
                  ->orWhere('phone', 'like', "%{$q}%")
                  ->orWhereHas('products', fn ($p) => $p
                      ->where('account_num', 'like', "%{$q}%")
                      ->orWhere('contract_no', 'like', "%{$q}%"));
            });
        }

        // ---- Filter ----
        if ($request->filled('product_group')) {
            $group = $request->product_group;

            $query->whereHas('products', fn ($p) => $p->where('product_group', $group));
        }

        if ($request->filled('subsegment')) {
            $query->where('subsegment', $request->subsegment);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sto')) {
            $query->where('sto', $request->sto);
        }

        if ($request->filled('payment_status')) {
            $query->where(function ($sub) {
                $sub->select('status')
                    ->from('payments')
                    ->whereColumn('payments.customer_id', 'customers.id')
                    ->orderByDesc('invoice_date')
                    ->limit(1);
            }, $request->payment_status);
        }

        $customers = $query->orderBy('id')->paginate(10)->withQueryString();

        return view('customers.index', [
            'customers'     => $customers,
            'productGroups' => CustomerProduct::whereNotNull('product_group')->distinct()->orderBy('product_group')->pluck('product_group'),
            'subsegments'   => Customer::whereNotNull('subsegment')->distinct()->orderBy('subsegment')->pluck('subsegment'),
            'stos'          => Customer::whereNotNull('sto')->distinct()->orderBy('sto')->pluck('sto'),
        ]);
    }

    // ---------------------------------------------------------------- TAMBAH MANUAL
    public function create()
    {
        return view('customers.create', [
            'industries'    => Customer::whereNotNull('industry')->distinct()->orderBy('industry')->pluck('industry'),
            'subsegments'   => Customer::whereNotNull('subsegment')->distinct()->orderBy('subsegment')->pluck('subsegment'),
            'datels'        => Customer::select('wilayah')->distinct()->orderBy('wilayah')->pluck('wilayah'),
            'stos'          => Customer::whereNotNull('sto')->distinct()->orderBy('sto')->pluck('sto'),
            'productGroups' => CustomerProduct::whereNotNull('product_group')->distinct()->orderBy('product_group')->pluck('product_group'),
            'productNames'  => CustomerProduct::whereNotNull('product_name')->distinct()->orderBy('product_name')->pluck('product_name'),
        ]);
    }

    public function store(Request $request, DummyActivityGenerator $generator)
    {
        $data = $request->validate([
            'customer_code' => ['required', 'string', 'max:30', 'unique:customers,customer_code'],
            'name'          => ['required', 'string', 'max:150'],
            'industry'      => ['nullable', 'string', 'max:100'],
            'subsegment'    => ['nullable', 'string', 'max:100'],
            'wilayah'       => ['required', 'string', 'max:100'],
            'sto'           => ['nullable', 'string', 'max:100'],
            'am_name'       => ['nullable', 'string', 'max:100'],
            'status'        => ['required', 'in:aktif,nonaktif'],
            'account_num'   => ['nullable', 'string', 'max:30'],
            'product_group' => ['required', 'string', 'max:100'],
            'product_name'  => ['required', 'string', 'max:100'],
            'circuit_speed' => ['nullable', 'string', 'max:30'],
            'contract_no'   => ['nullable', 'string', 'max:40'],
            'start_date'    => ['nullable', 'date'],
            'abonemen'      => ['nullable', 'integer', 'min:0'],
        ], [
            'customer_code.required' => 'NIPNAS wajib diisi.',
            'customer_code.unique'   => 'NIPNAS ini sudah terdaftar.',
            'name.required'          => 'Nama pelanggan wajib diisi.',
            'wilayah.required'       => 'SA_DATEL wajib diisi.',
            'product_group.required' => 'PRODUCT_GROUP wajib diisi.',
            'product_name.required'  => 'PRODUCT_NAME wajib diisi.',
            'start_date.date'        => 'Tanggal mulai tidak valid.',
            'abonemen.integer'       => 'ABONEMEN harus berupa angka tanpa titik/koma.',
        ]);

        DB::transaction(function () use ($data) {
            $customer = Customer::create([
                'customer_code'   => $data['customer_code'],
                'name'            => $data['name'],
                'industry'        => $data['industry'] ?? null,
                'subsegment'      => $data['subsegment'] ?? null,
                'wilayah'         => $data['wilayah'],
                'sto'             => $data['sto'] ?? null,
                'am_name'         => $data['am_name'] ?? null,
                'internet_number' => $data['account_num'] ?? null,
                'product'         => $data['product_name'],
                'status'          => $data['status'],
                'install_date'    => $data['start_date'] ?? now()->toDateString(),
                'monthly_revenue' => $data['abonemen'] ?? 0,
                'health_score'    => 100,
            ]);

            $customer->products()->create([
                'account_num'   => $data['account_num'] ?? null,
                'product_group' => $data['product_group'],
                'product_name'  => $data['product_name'],
                'circuit_speed' => $data['circuit_speed'] ?? null,
                'contract_no'   => $data['contract_no'] ?? null,
                'start_date'    => $data['start_date'] ?? null,
                'abonemen'      => $data['abonemen'] ?? null,
                'revenue'       => $data['abonemen'] ?? 0,
            ]);
        });

        // Pelanggan baru belum punya pembayaran/pemakaian: isi dummy kalau dicentang
        if ($request->boolean('dummy')) {
            $generator->generate();
        }

        Cache::forget('topbar.notifications');

        return redirect()
            ->route('customers.index', ['q' => $data['customer_code']])
            ->with('status', "Pelanggan {$data['name']} berhasil ditambahkan.");
    }

    // ---------------------------------------------------------------- UPLOAD CSV
    public function import(
        Request $request,
        MasterImporter $master,
        PaymentImporter $payments,
        DummyActivityGenerator $generator
    ) {
        $request->validate([
            'type'           => ['required', 'in:master,payments'],
            'csv'            => ['required', 'file', 'max:10240', 'extensions:csv,txt'],
            'revenue_months' => ['nullable', 'in:1,12'],
        ], [
            'type.required'  => 'Pilih jenis data yang diupload.',
            'csv.required'   => 'Pilih file CSV dulu.',
            'csv.extensions' => 'File harus berformat .csv',
            'csv.max'        => 'Ukuran file maksimal 10 MB.',
        ]);

        set_time_limit(300);

        $path = $request->file('csv')->getRealPath();

        try {
            // ---------- Riwayat pembayaran ----------
            if ($request->input('type') === 'payments') {
                $s = $payments->import($path);

                $pesan = "Riwayat pembayaran: {$s['rows']} baris dibaca, {$s['inserted']} tagihan masuk untuk {$s['customers']} pelanggan.";

                if ($s['unknown_rows'] > 0) {
                    $pesan .= "\n{$s['unknown_rows']} baris dilewati karena NIPNAS tidak ada di data pelanggan"
                            . ' (contoh: ' . implode(', ', $s['unknown_codes']) . '). Upload data pelanggan dulu.';
                }

                if ($s['bad_date'] > 0) {
                    $pesan .= "\n{$s['bad_date']} baris dilewati karena TANGGAL_TAGIHAN kosong atau formatnya tidak dikenali.";
                }

                if ($s['future'] > 0) {
                    $pesan .= "\n{$s['future']} baris dilewati karena tagihan belum terbit dan belum dibayar.";
                }

                return redirect()
                    ->route('customers.index')
                    ->with($s['inserted'] > 0 ? 'status' : 'error', $pesan);
            }

            // ---------- Data pelanggan (master) ----------
            $stats = $master->import($path, (int) $request->input('revenue_months', 1));
        } catch (\RuntimeException $e) {
            return redirect()->route('customers.index')->with('error', 'Import gagal. ' . $e->getMessage());
        }

        $pesan = "Import selesai: {$stats['rows']} baris dibaca menjadi {$stats['customers']} pelanggan "
               . "({$stats['created']} baru, {$stats['updated']} diperbarui) dengan {$stats['products']} layanan.";

        if ($stats['skipped'] > 0) {
            $pesan .= "\n{$stats['skipped']} baris dilewati karena nama pelanggan kosong.";
        }

        if ($request->boolean('dummy')) {
            $jumlah = $generator->generate();
            $pesan .= "\nData pembayaran & pemakaian dummy dibuat untuk {$jumlah} pelanggan.";
        }

        return redirect()->route('customers.index')->with('status', $pesan);
    }

    // ---------------------------------------------------------------- DETAIL
    public function show(Customer $customer, CustomerAnalyzer $analyzer)
    {
        $customer->load('products');

        $payment  = $analyzer->paymentBehavior($customer);
        $overview = $analyzer->overview($customer, $payment);
        $insight  = $analyzer->insights($customer, $payment);

        $bulan = CustomerAnalyzer::BULAN;

        // ---- Data grafik (dikirim ke JS lewat atribut data-chart) ----
        $charts = [
            'payTrend' => [
                'labels' => $payment['payments']->map(fn ($p) => $bulan[$p->invoice_date->month - 1])->all(),
                'values' => $payment['payments']->pluck('delay_days')->all(),
            ],
            'payDonut' => [
                'labels' => ['Tepat waktu', 'Terlambat', 'Tunggakan'],
                'values' => array_values($payment['distribution']),
            ],
        ];

        return view('customers.show', [
            'customer' => $customer,
            'payment'  => $payment,
            'history'  => $payment['payments']->reverse()->values(), // terbaru di atas
            'overview' => $overview,
            'insight'  => $insight,
            'charts'   => $charts,
        ]);
    }
}