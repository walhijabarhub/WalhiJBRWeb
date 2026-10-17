<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Services\AuditLogService;
use App\Services\CsvSanitizer;

class AdminSubscriberController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Subscriber::class);

        $subscribers = Subscriber::orderBy('created_at', 'desc')->paginate(20);

        return view('admin.subscribers.index', compact('subscribers'));
    }

    public function export()
    {
        $this->authorize('export', Subscriber::class);

        AuditLogService::log('SUBSCRIBER_EXPORT', 'Subscriber');

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="newsletter_subscribers_'.now()->format('Y-m-d').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Email', 'Status', 'Tanggal Daftar']);

            // Chunk records to prevent memory exhaustion
            // Only confirmed, still-subscribed addresses may be mailed (double opt-in).
            Subscriber::active()->orderBy('id')->chunk(500, function ($subscribers) use ($file) {
                foreach ($subscribers as $subscriber) {
                    fputcsv($file, [
                        $subscriber->id,
                        CsvSanitizer::sanitize($subscriber->email),
                        $subscriber->is_active ? 'Aktif' : 'Tidak Aktif',
                        $subscriber->created_at ? $subscriber->created_at->format('Y-m-d H:i:s') : '',
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function destroy(Subscriber $subscriber)
    {
        $this->authorize('delete', $subscriber);

        AuditLogService::log('SUBSCRIBER_DELETE', 'Subscriber', $subscriber->id);

        $subscriber->delete();

        return redirect()->back()->with('success', 'Pelanggan berhasil dihapus.');
    }
}
