<?php

namespace App\Http\Controllers;

use App\Enums\ContentStatus;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Content;

class CommentController extends Controller
{
    /**
     * Store a new comment for a content item.
     * Honeypot check is delegated to StoreCommentRequest.
     */
    public function store(StoreCommentRequest $request, Content $content)
    {
        // Only published content takes comments; anything else is 404, as on the detail page.
        abort_if($content->status !== ContentStatus::Published->value, 404);

        // Silently ignore spam submissions to avoid revealing detection to bots
        if ($request->isSpam()) {
            return redirect()->back()->with(
                'comment_success',
                'Komentar Anda berhasil dikirim dan sedang menunggu moderasi admin.'
            );
        }

        if ($request->filled('parent_id')) {
            $parent = Comment::where('id', $request->input('parent_id'))
                ->where('content_id', $content->id)
                ->whereNull('parent_id')
                ->first();

            if (! $parent) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['parent_id' => 'Komentar induk tidak valid untuk konten ini.']);
            }
        }

        $comment = new Comment($request->validated());
        $comment->content_id = $content->id;
        $comment->status = 'pending';
        $comment->save();

        return redirect()->back()->with(
            'comment_success',
            'Komentar Anda berhasil dikirim dan sedang menunggu moderasi admin.'
        );
    }
}
