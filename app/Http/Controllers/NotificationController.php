<?php

namespace App\Http\Controllers;

use App\Services\PaymentBehaviorService;

class NotificationController extends Controller
{
    public function index(PaymentBehaviorService $service)
    {
        // Ambil hasil analisis pembayaran pelanggan
        $rows = $service->analyzeAll();

        // Ambil seluruh peringatan pembayaran
        $notifications = $service->notifications($rows);

        // Hitung jumlah peringatan berdasarkan kategori
        $counts = collect($notifications)->countBy('type');

        // Tampilkan halaman notifikasi
        return view('notifications.index', [
            'notifications' => $notifications,
            'counts' => $counts,
        ]);
    }
}
