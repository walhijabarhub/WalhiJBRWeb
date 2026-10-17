<?php

namespace App\Http\Controllers;

use App\Http\Requests\Newsletter\SubscribeRequest;
use App\Models\Subscriber;
use App\Notifications\ConfirmNewsletterSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    private const PENDING_MESSAGE = 'Terima kasih! Silakan cek email Anda dan klik tautan konfirmasi untuk menyelesaikan pendaftaran.';

    /**
     * Start a double opt-in subscription. The response is the same whether or not the
     * address is already subscribed, so the form cannot be used to probe the list.
     */
    public function subscribe(SubscribeRequest $request): RedirectResponse
    {
        // Silently ignore spam to avoid revealing detection to bots
        if ($request->isSpam()) {
            return redirect()->back()->with('subscribe_success', self::PENDING_MESSAGE);
        }

        $email = Str::lower($request->validated('email'));
        $subscriber = Subscriber::withTrashed()->where('email', $email)->first();

        if ($subscriber && ! $subscriber->trashed() && $subscriber->isConfirmed() && $subscriber->is_active) {
            return redirect()->back()->with('subscribe_success', self::PENDING_MESSAGE);
        }

        // Re-sending is limited per address so the form cannot be used to flood an inbox.
        if ($subscriber && ! $subscriber->trashed() && ! $subscriber->isConfirmed() && $subscriber->updated_at?->gt(now()->subMinutes(10))) {
            return redirect()->back()->with('subscribe_success', self::PENDING_MESSAGE);
        }

        if ($subscriber?->trashed()) {
            $subscriber->restore();
        }

        $subscriber ??= Subscriber::create(['email' => $email, 'is_active' => false]);
        $subscriber->forceFill(['is_active' => false, 'confirmed_at' => null])->touch();

        try {
            Notification::route('mail', $subscriber->email)->notify(new ConfirmNewsletterSubscription($subscriber));
        } catch (\Throwable $e) {
            Log::error('Newsletter confirmation email could not be sent', ['error' => $e->getMessage()]);
        }

        return redirect()->back()->with('subscribe_success', self::PENDING_MESSAGE);
    }

    /**
     * Signed link from the confirmation email.
     */
    public function confirm(Subscriber $subscriber): RedirectResponse
    {
        $subscriber->forceFill([
            'is_active' => true,
            'confirmed_at' => $subscriber->confirmed_at ?? now(),
        ])->save();

        return redirect()->route('home')->with('subscribe_success', 'Langganan Nawala Anda telah dikonfirmasi. Terima kasih!');
    }

    /**
     * Show the unsubscribe confirmation. A GET never changes state, so link scanners
     * in mail clients cannot unsubscribe people by prefetching the URL.
     */
    public function showUnsubscribe(string $token): View
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();

        return view('newsletter.unsubscribe', ['subscriber' => $subscriber, 'done' => false]);
    }

    public function unsubscribe(string $token): View
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();
        $subscriber->forceFill(['is_active' => false])->save();

        return view('newsletter.unsubscribe', ['subscriber' => $subscriber, 'done' => true]);
    }
}
