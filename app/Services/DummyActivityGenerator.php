<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DummyActivityGenerator
{
    // Hanya membuat data pemakaian dan layanan.
    // Pembayaran sekarang berasal dari CSV.
    public function generate(): int
    {
        $today = today();
        $count = 0;

        Customer::query()
            ->whereDoesntHave('usages')
            ->with('products')
            ->chunkById(200, function ($customers) use ($today, &$count) {
                $usages = [];
                $services = [];
                $now = now();

                foreach ($customers as $c) {
                    $quota = [150, 300, 500, 900][mt_rand(0, 3)];
                    $base = mt_rand(40, 95) / 100;
                    $arah = mt_rand(1, 100);

                    $last = ['download' => 0, 'ratio' => 0];

                    for ($i = 9; $i >= 1; $i--) {
                        $step = 9 - $i;

                        $factor = $arah <= 20
                            ? 1 - $step * 0.05
                            : ($arah <= 40
                                ? 0.6 + $step * 0.05
                                : 1);

                        $ratio = max(
                            0.1,
                            min(1.05, $base * $factor *
                                (mt_rand(90, 110) / 100))
                        );

                        $download = (int) round($quota * $ratio);

                        $usages[] = [
                            'customer_id' => $c->id,
                            'period' => $today->copy()
                                ->startOfMonth()
                                ->subMonthsNoOverflow($i)
                                ->toDateString(),
                            'download_gb' => $download,
                            'upload_gb' => (int) round(
                                $download * mt_rand(30, 70) / 100
                            ),
                            'online_hours' => mt_rand(30, 100) / 10,
                            'active_devices' => mt_rand(1, 6),
                            'quota_gb' => $quota,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        $last = [
                            'download' => $download,
                            'ratio' => $download / $quota,
                        ];
                    }

                    $first = $c->products->first();

                    $services[] = [
                        'customer_id' => $c->id,
                        'service' => 'Internet',
                        'package' => $first?->circuit_speed
                            ?: ($first?->product_name ?: '-'),
                        'used_value' => $last['download'],
                        'limit_value' => $quota,
                        'unit' => 'GB',
                        'status' => $last['ratio'] >= 0.9
                            ? 'limit'
                            : ($last['ratio'] >= 0.75
                                ? 'waspada'
                                : 'normal'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $count++;
                }

                DB::transaction(function () use ($usages, $services) {
                    foreach (array_chunk($usages, 500) as $part) {
                        DB::table('usages')->insert($part);
                    }

                    foreach (array_chunk($services, 500) as $part) {
                        DB::table('customer_services')->insert($part);
                    }
                });
            });

        Cache::forget('topbar.notifications');

        return $count;
    }
}
