<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Services\Content\ViewCounter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    /**
     * Whitelist of acceptable document extensions for secure downloads.
     */
    private const ALLOWED_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'rtf', 'csv', 'txt',
    ];

    /**
     * Serve an authorized document download.
     *
     * Guardrails:
     * 1. Access control via ContentPolicy@view (404 for guest on draft to prevent enumeration; 403 for unauthorized user).
     * 2. Rejects external URLs (does not act as an open redirector).
     * 3. Extension is determined strictly from the validated physical file, not user input.
     * 4. Safe filename sanitized against CRLF and Content-Disposition header injection.
     * 5. Served with X-Content-Type-Options: nosniff and Content-Disposition: attachment.
     */
    public function download(Request $request, Content $content): StreamedResponse
    {
        // 1. Policy-driven authorization
        if (! auth()->check()) {
            // Guests attempting to access non-published content get 404 (prevent resource enumeration)
            if ($content->status !== 'published') {
                abort(404);
            }
        } else {
            // Authenticated users must satisfy the Policy view rule (returns 403 on authorization failure)
            $this->authorize('view', $content);
        }

        // 2. Reject external URLs: this endpoint strictly serves local storage documents
        $rawPath = (string) $content->getRawOriginal('image_url');
        if (empty($rawPath) || str_starts_with($rawPath, 'http://') || str_starts_with($rawPath, 'https://') || str_starts_with($rawPath, '//')) {
            abort(404, 'Dokumen lokal tidak tersedia untuk diunduh melalui endpoint ini.');
        }

        // 3. Resolve the physical file (basename only, so no path traversal): private
        //    disk first, then the legacy public disk. Same lookup as Content::$has_download.
        $location = $content->documentFileLocation();
        if ($location === null) {
            abort(404, 'Berkas dokumen fisik tidak ditemukan di server.');
        }
        [$disk, $relativePath] = $location;

        // 4. Determine file extension strictly from physical file path
        $fileExt = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        if (! in_array($fileExt, self::ALLOWED_EXTENSIONS, true)) {
            abort(403, 'Tipe berkas dokumen tidak diizinkan untuk diunduh.');
        }

        // 5. Generate sanitized filename immune to Content-Disposition header injection (CRLF / quote stripping)
        $safeBase = Str::slug($content->title ?: 'dokumen-walhi-jabar');
        if (empty($safeBase)) {
            $safeBase = 'dokumen-walhi-jabar';
        }
        // Strict ASCII alphanumeric + hyphen only, max 80 chars
        $safeBase = substr(preg_replace('/[^a-zA-Z0-9\-]/', '', $safeBase), 0, 80);
        $downloadFilename = "{$safeBase}.{$fileExt}";

        // 6. Deduplicated view / download counter increment
        $sessionKey = 'downloaded_content_'.$content->id;
        if (! session()->has($sessionKey)) {
            ViewCounter::record($content);
            session()->put($sessionKey, true);
        }

        // 7. Security Headers: nosniff, explicit attachment disposition, appropriate cache control
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $downloadFilename),
        ];

        if ($content->status !== 'published') {
            $headers['Cache-Control'] = 'private, no-cache, no-store, must-revalidate';
            $headers['Pragma'] = 'no-cache';
        } else {
            $headers['Cache-Control'] = 'public, max-age=3600';
        }

        return Storage::disk($disk)->download($relativePath, $downloadFilename, $headers);
    }
}
