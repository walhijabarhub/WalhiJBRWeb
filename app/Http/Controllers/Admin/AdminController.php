<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Donation;
use App\Models\Subscriber;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function index()
    {
        $stats = Cache::remember('admin_dashboard_stats', 300, function () {
            $totalArticles = Content::publishable()->count();
            $articlesThisMonth = Content::publishable()->where('created_at', '>=', now()->startOfMonth())->count();

            $activeDonations = Content::ofCategory('donasi')->published()->count();
            $activeEvents = Content::ofCategory('pekan-rakyat')->published()->count();

            $totalDonationsAmount = (int) Donation::where('status', 'success')->sum('amount');
            $successfulDonationsCount = Donation::where('status', 'success')->count();
            $totalViews = (int) Content::sum('views');

            [$labels, $monthsData] = $this->buildMonthlyChart();

            return [
                'total_articles' => $totalArticles,
                'articles_this_month' => $articlesThisMonth,
                'active_campaigns' => $activeDonations + $activeEvents,
                'active_donations' => $activeDonations,
                'active_events' => $activeEvents,
                'total_donations_amount' => $totalDonationsAmount,
                'successful_donations_count' => $successfulDonationsCount,
                'total_views' => $totalViews,
                'chart_labels' => $labels,
                'chart_data' => $monthsData,
                'has_donations' => array_sum($monthsData) > 0,
            ];
        });

        $recentTransactions = Donation::orderBy('created_at', 'desc')->take(5)->get();

        $latestPostings = Content::publishable()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $recentActivities = $this->getRecentActivities();

        return view('admin.dashboard', compact('stats', 'recentTransactions', 'latestPostings', 'recentActivities'));
    }

    /**
     * Collect and merge real activities from donations, comments, contents, and subscribers.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecentActivities(): array
    {
        $activities = collect();

        // 1. Recent Donations
        $donations = Donation::latest()->take(10)->get();
        foreach ($donations as $donation) {
            $activities->push([
                'type' => 'donation',
                'title' => 'Donasi Masuk',
                'detail' => 'Rp '.number_format($donation->amount, 0, ',', '.').' dari '.($donation->donor_name ?: 'Anonim').($donation->status !== 'success' ? ' ('.ucfirst($donation->status).')' : ''),
                'icon' => 'heart',
                'bg' => 'bg-[#eaf4ee]',
                'color' => 'text-[#256D4A]',
                'time' => $donation->created_at ? $donation->created_at->diffForHumans() : 'baru saja',
                'timestamp' => $donation->created_at,
                'link' => route('admin.content.index', 'donasi'),
            ]);
        }

        // 2. Recent Comments
        $comments = Comment::with('content')->latest()->take(10)->get();
        foreach ($comments as $comment) {
            $contentTitle = $comment->content ? ' di "'.Str::limit($comment->content->title, 25).'"' : '';
            $activities->push([
                'type' => 'comment',
                'title' => 'Komentar Baru',
                'detail' => '"'.Str::limit($comment->body, 30).'" oleh '.$comment->author_name.$contentTitle,
                'icon' => 'message-square',
                'bg' => 'bg-[#f0f5f0]',
                'color' => 'text-[#5C8D59]',
                'time' => $comment->created_at ? $comment->created_at->diffForHumans() : 'baru saja',
                'timestamp' => $comment->created_at,
                'link' => route('admin.comments.index'),
            ]);
        }

        // 3. Recent Published Content
        $contents = Content::publishable()->latest()->take(10)->get();
        foreach ($contents as $content) {
            $catName = ucfirst(str_replace('-', ' ', $content->category));
            $activities->push([
                'type' => 'content',
                'title' => "Publikasi: {$catName}",
                'detail' => Str::limit($content->title, 38).' oleh '.($content->author ?: 'WALHI Jabar'),
                'icon' => 'file-text',
                'bg' => 'bg-[#f4faf6]',
                'color' => 'text-[#256D4A]',
                'time' => $content->created_at ? $content->created_at->diffForHumans() : 'baru saja',
                'timestamp' => $content->created_at,
                'link' => route('content.show', $content->slug),
            ]);
        }

        // 4. Recent Newsletter Subscribers
        $subscribers = Subscriber::latest()->take(10)->get();
        foreach ($subscribers as $subscriber) {
            $maskedEmail = Str::mask($subscriber->email, '*', 3, 5);
            $activities->push([
                'type' => 'subscriber',
                'title' => 'Langganan Newsletter',
                'detail' => "Email {$maskedEmail} telah terdaftar.",
                'icon' => 'send',
                'bg' => 'bg-[#fdf0ee]',
                'color' => 'text-[#D95C3F]',
                'time' => $subscriber->created_at ? $subscriber->created_at->diffForHumans() : 'baru saja',
                'timestamp' => $subscriber->created_at,
                'link' => route('admin.subscribers.index'),
            ]);
        }

        return $activities->sortByDesc('timestamp')->take(15)->values()->all();
    }

    /**
     * Build monthly donation chart data (real data only — no mock fallback).
     * Returns [$labels, $data] for the last 12 months.
     *
     * @return array{0: array<string>, 1: array<int>}
     */
    private function buildMonthlyChart(): array
    {
        $labels = [];
        $data = [];

        $startDate = Carbon::now()->startOfMonth()->subMonths(11);

        $driver = \DB::getDriverName();
        $dateExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at) as period_key"
            : "DATE_FORMAT(created_at, '%Y-%m') as period_key";

        $monthlyTotals = Donation::where('status', 'success')
            ->where('created_at', '>=', $startDate)
            ->selectRaw("{$dateExpr}, SUM(amount) as total")
            ->groupBy('period_key')
            ->pluck('total', 'period_key');

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->startOfMonth()->subMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->translatedFormat("M 'y");
            $data[] = (int) ($monthlyTotals[$key] ?? 0);
        }

        return [$labels, $data];
    }
}
