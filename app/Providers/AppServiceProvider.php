<?php

namespace App\Providers;

use App\Services\InsightService;
use App\Services\PaymentBehaviorService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Setiap topbar dirender, kirim data notifikasi ke view-nya
        View::composer('partials.topbar', function ($view) {
            if (! auth()->check()) {
                return;
            }

            // Analisis cukup berat kalau datanya besar, jadi di-cache 5 menit
            $notif = Cache::remember('topbar.notifications', 300, function () {
                $payment = app(PaymentBehaviorService::class);
                $insight = app(InsightService::class);

                $all = array_merge(
                    $payment->notifications($payment->analyzeAll()),
                    $insight->notifications($insight->analyzeAll())
                );

                return [
                    'total' => count($all),
                    'items' => array_slice($all, 0, 8),
                ];
            });

            $view->with('notif', $notif);
        });
    }
}