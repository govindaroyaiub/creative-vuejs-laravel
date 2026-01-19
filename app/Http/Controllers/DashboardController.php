<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use App\Models\FileTransfer;
use App\Models\Bill;
use App\Models\newPreview;
use App\Models\newCategory;
use App\Models\newFeedback;
use App\Models\newFeedbackSet;
use App\Models\newVersion;
use App\Models\newBanner;
use App\Models\newGif;
use App\Models\newVideo;
use App\Models\newSocial;
use App\Models\Client;

class DashboardController extends Controller
{
    public function index()
    {
        $year = now()->year;

        $monthlyStats = [
            'banners' => $this->getMonthlyCount(newBanner::class, $year),
            'videos' => $this->getMonthlyCount(newVideo::class, $year),
            'gifs' => $this->getMonthlyCount(newGif::class, $year),
            'socials' => $this->getMonthlyCount(newSocial::class, $year),
        ];

        $monthlyPreviewStats = $this->getMonthlyPreviewCount(newPreview::class, $year);

        $clientIdOfLoggedInUser = Auth::user()->client_id;
        $client = Client::find($clientIdOfLoggedInUser);

        if ($client['name'] == 'Planet Nine') {
            return Inertia::render('Dashboard', [
                'userCount' => User::count(),
                'previewCount' => newPreview::whereYear('created_at', $year)->count(),
                'bannerCount' => newBanner::whereYear('created_at', $year)->count(),
                'videoCount' => newVideo::whereYear('created_at', $year)->count(),
                'gifCount' => newGif::whereYear('created_at', $year)->count(),
                'socialCount' => newSocial::whereYear('created_at', $year)->count(),
                'fileTransferCount' => FileTransfer::whereYear('created_at', $year)->count(),
                'totalBill' => Bill::whereYear('created_at', $year)->sum('total_amount'),
                'monthlyStats' => $monthlyStats,
                'monthlyBillTotals' => $this->getMonthlyBillTotals($year),
                'monthlyPreviewStats' => $monthlyPreviewStats,
            ]);
        } else {
            return Inertia::render('GuestWelcome', [
                'username' => Auth::user()->name,
            ]);
        }
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

    private function getMonthlyBillTotals($year)
    {
        return Bill::selectRaw('MONTH(created_at) as month, SUM(total_amount) as total')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(fn($row) => [intval($row->month) => floatval($row->total)])
            ->all();
    }

    private function getMonthlyPreviewCount($model, $year)
    {
        return newPreview::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(fn($row) => [intval($row->month) => $row->count])
            ->all();
    }
}
