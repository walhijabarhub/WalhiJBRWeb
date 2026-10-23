<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Donation;
use App\Models\Subscriber;
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

            $donationTotals = Donation::successful()->toBase()
                ->selectRaw('COUNT(*) AS donations, COALESCE(SUM(amount), 0) AS amount')
                ->first();
            $totalDonationsAmount = (int) $donationTotals->amount;
            $successfulDonationsCount = (int) $donationTotals->donations;
            $totalViews = (int) Content::sum('views');

            [$labels, $monthsData] = Donation::monthlySuccessChart();

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

        $latestPostings = Content::publishable()
            ->select(['id', 'title', 'slug', 'category', 'status', 'author', 'publish_date', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $recentActivities = $this->getRecentActivities();

        return view('admin.dashboard', compact('stats', 'latestPostings', 'recentActivities'));
    }

    /**
     * Collect and merge real activities from donations, comments, contents, and subscribers.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecentActivities(): array
    {
        $activities = collect();

        // 1. Recent Donations: donor names are personal data, shown only to roles that may
        //    open the donation dashboard (ContentPolicy@viewCategory, admin-only).
        $donations = request()->user()?->can('viewCategory', [Content::class, 'donasi'])
            ? Donation::latest()->take(10)->get()
            : collect();
        foreach ($donations as $donation) {
            $activities->push([
                'type' => 'donation',
                'title' => 'Donasi Masuk',
                'detail' => 'Rp '.number_format($donation->amount, 0, ',', '.').' dari '.($donation->donor_name ?: 'Anonim').($donation->is_success ? '' : ' ('.$donation->status_label.')'),
                'icon' => 'heart',
                'bg' => 'bg-[#eaf4ee]',
                'color' => 'text-[#256D4A]',
                'time' => $donation->created_at ? $donation->created_at->diffForHumans() : 'baru saja',
                'timestamp' => $donation->created_at,
                'link' => route('admin.content.index', 'donasi'),
            ]);
        }

        // 2. Recent Comments
        $comments = Comment::with('content:id,title')->latest()->take(10)->get();
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
        $contents = Content::publishable()
            ->select(['id', 'title', 'slug', 'category', 'author', 'created_at'])
            ->latest()
            ->take(10)
            ->get();
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
}
