<?php

namespace Database\Seeders;

use App\Models\Customer;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    private $faker;
    private Carbon $today;

    // harga bulanan & kuota (GB) per produk
    private array $products = [
        'Indibiz 20 Mbps'  => ['amount' => 250000, 'quota' => 150],
        'Indibiz 50 Mbps'  => ['amount' => 350000, 'quota' => 300],
        'Indibiz 100 Mbps' => ['amount' => 550000, 'quota' => 500],
        'Indibiz 200 Mbps' => ['amount' => 850000, 'quota' => 900],
    ];

    public function run(): void
    {
        $this->faker = Faker::create('id_ID');
        $this->faker->seed(42);
        $this->today = Carbon::create(2026, 10, 5);

        // 1) Pelanggan contoh sesuai desain (Matt Dickerson)
        //    Pola delay per bulan Jan..Okt: Mar telat 1 hari, Agu telat 2 hari
        $this->buatPelanggan([
            'customer_code'   => '20462',
            'name'            => 'Matt Dickerson',
            'phone'           => '0812 3456 789',
            'internet_number' => '998223711937',
            'product'         => 'Indibiz 50 Mbps',
            'industry'        => 'Perumahan',
            'status'          => 'aktif',
            'wilayah'         => 'Blimbing',
            'install_date'    => '2024-01-01',
            'address'         => 'Jalanin dulu ajaa',
            'monthly_revenue' => 350000,
        ], [0, 0, 1, 0, 0, 0, 0, 2, 0, 0], 88);

        // 2) 29 pelanggan acak dengan pola bayar berbeda
        $polaList = ['rajin', 'rajin', 'rajin', 'campur', 'sering_telat', 'menunggak'];

        for ($i = 0; $i < 29; $i++) {
            $pola    = $polaList[$i % count($polaList)];
            $product = $this->faker->randomElement(array_keys($this->products));

            $this->buatPelanggan([
                'customer_code'   => (string) (20463 + $i),
                'name'            => $this->faker->name(),
                'phone'           => $this->faker->numerify('08## #### ####'),
                'internet_number' => '99822' . $this->faker->unique()->numerify('#######'),
                'product'         => $product,
                'industry'        => $this->faker->randomElement(['F&B', 'Retail', 'Pendidikan', 'Perhotelan', 'Kesehatan', 'Perumahan']),
                'status'          => ($pola === 'menunggak' && $this->faker->boolean(30)) ? 'nonaktif' : 'aktif',
                'wilayah'         => $this->faker->randomElement(['Batu', 'Bumiaji', 'Junrejo', 'Blimbing']),
                'install_date'    => $this->faker->dateTimeBetween('-3 years', '-3 months')->format('Y-m-d'),
                'address'         => $this->faker->address(),
                'monthly_revenue' => $this->products[$product]['amount'],
            ], $pola);
        }
    }

    private function buatPelanggan(array $data, array|string $pola, ?int $skor = null): void
    {
        $customer = Customer::create($data + ['health_score' => 0]);
        $produk   = $this->products[$data['product']];

        // ---- Riwayat tagihan Jan..Okt 2026 ----
        for ($m = 1; $m <= 10; $m++) {
            $invoice = Carbon::create(2026, $m, 1);
            $delay   = is_array($pola) ? $pola[$m - 1] : $this->delayUntuk($pola, $m);

            // Okt baru tanggal 5, jadi pembayaran tidak boleh melewati hari ini
            if ($m === 10 && $delay !== null) {
                $delay = min($delay, 4);
            }

            $customer->payments()->create([
                'invoice_date' => $invoice,
                'paid_date'    => $delay === null ? null : $invoice->copy()->addDays($delay),
                'amount'       => $produk['amount'],
                'delay_days'   => $delay ?? (int) $invoice->diffInDays($this->today, true),
                'status'       => match (true) {
                    $delay === null => 'tunggakan',
                    $delay === 0    => 'tepat_waktu',
                    default         => 'terlambat',
                },
            ]);
        }

        // ---- Pemakaian Jan..Sep 2026 ----
        $downloadTerakhir = 0;
        for ($m = 1; $m <= 9; $m++) {
            $download = (int) round($produk['quota'] * $this->faker->randomFloat(2, 0.4, 1.0));
            $downloadTerakhir = $download;

            $customer->usages()->create([
                'period'         => Carbon::create(2026, $m, 1),
                'download_gb'    => $download,
                'upload_gb'      => (int) round($download * $this->faker->randomFloat(2, 0.3, 0.7)),
                'online_hours'   => $this->faker->randomFloat(1, 3, 10),
                'active_devices' => $this->faker->numberBetween(1, 6),
                'quota_gb'       => $produk['quota'],
            ]);
        }

        // ---- Detail layanan ----
        $rasio = $downloadTerakhir / $produk['quota'];
        $customer->services()->create([
            'service'     => 'Internet',
            'package'     => str_replace('Indibiz ', '', $data['product']),
            'used_value'  => $downloadTerakhir,
            'limit_value' => $produk['quota'],
            'unit'        => 'GB',
            'status'      => $rasio >= 0.9 ? 'limit' : ($rasio >= 0.75 ? 'waspada' : 'normal'),
        ]);
        $customer->services()->create([
            'service'     => 'Telepon',
            'package'     => 'Unlimited',
            'used_value'  => $this->faker->numberBetween(60, 300),
            'limit_value' => null,
            'unit'        => 'menit',
            'status'      => 'normal',
        ]);
        if ($this->faker->boolean(40)) {
            $customer->services()->create([
                'service'     => 'TV',
                'package'     => 'Entertainment',
                'used_value'  => $this->faker->numberBetween(10, 80),
                'limit_value' => null,
                'unit'        => 'jam',
                'status'      => 'normal',
            ]);
        }

        // ---- Health score ----
        if ($skor === null) {
            $telat    = $customer->payments()->where('status', 'terlambat')->count();
            $tunggak  = $customer->payments()->where('status', 'tunggakan')->count();
            $skor     = max(30, 100 - ($telat * 4) - ($tunggak * 20));
        }
        $customer->update(['health_score' => $skor]);
    }

    // null = belum bayar (tunggakan), angka = berapa hari telat dari tanggal tagihan
    private function delayUntuk(string $pola, int $bulan): ?int
    {
        return match ($pola) {
            'rajin'        => $this->faker->randomElement([0, 0, 0, 0, 1]),
            'sering_telat' => $this->faker->numberBetween(2, 9),
            'campur'       => $this->faker->randomElement([0, 0, 2, 4, 7]),
            'menunggak'    => $bulan >= 9 ? null : $this->faker->randomElement([0, 0, 0, 3]), // Sep & Okt belum bayar
            default        => 0,
        };
    }
}