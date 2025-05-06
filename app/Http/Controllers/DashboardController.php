<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\SubBanner;
use App\Models\SubVideo;
use App\Models\SubGif;
use App\Models\SubSocial;
use App\Models\Preview;
use App\Models\FileTransfer;
use App\Models\SubBill;

class DashboardController extends Controller
{
    public function index()
    {
        $year = now()->year;

        $monthlyStats = [
            'banners' => $this->getMonthlyCount(SubBanner::class, $year),
            'videos' => $this->getMonthlyCount(SubVideo::class, $year),
            'gifs' => $this->getMonthlyCount(SubGif::class, $year),
            'socials' => $this->getMonthlyCount(SubSocial::class, $year),
        ];


        return Inertia::render('Dashboard', [
            'userCount' => User::count(),
            'previewCount' => Preview::whereYear('created_at', $year)->count(),
            'bannerCount' => SubBanner::whereYear('created_at', $year)->count(),
            'videoCount' => SubVideo::whereYear('created_at', $year)->count(),
            'gifCount' => SubGif::whereYear('created_at', $year)->count(),
            'socialCount' => SubSocial::whereYear('created_at', $year)->count(),
            'fileTransferCount' => FileTransfer::whereYear('created_at', $year)->count(),
            'billCount' => SubBill::whereYear('created_at', $year)->count(),
            'monthlyStats' => $monthlyStats,
        ]);
    }

    private function getMonthlyCount($model, $year)
    {
        return $model::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(fn($row) => [intval($row->month) => $row->count])
            ->all();
    }
}