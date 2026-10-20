<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;

class NotificationService
{
    public function notifyAdminsNewReport(Report $report): void
    {
        $admins = User::role('admin')->where('is_active', true)->get();

        $typeLabel = match ($report->type) {
            'bullying' => 'Laporan Bullying Baru',
            'facility' => 'Laporan Fasilitas Baru',
            'aspiration' => 'Aspirasi Baru', 
            default => 'Laporan Baru',
        };

    foreach ($admins as $admin) {
        $admin->notifications()->create([
            'report_id' => $report->id,
            'title' => $typeLabel,
            'message' => "Laporan baru dengan kode {$report->report_code} membutuhkan perhatian.",
        ]);
    }
    }
}
