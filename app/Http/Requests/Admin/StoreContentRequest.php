<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesContentLinks;
use App\Models\Content;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreContentRequest extends FormRequest
{
    use ValidatesContentLinks;

    public function authorize(): bool
    {
        $category = (string) $this->route('category');
        if (Content::isSensitiveCategory($category)) {
            return $this->user() !== null && $this->user()->isAdmin();
        }

        return $this->user() !== null && $this->user()->canManageContent();
    }

    public function rules(): array
    {
        return array_merge([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:5000000'],
            'tags' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:published,draft,archived'],
            'image_url' => [
                'nullable',
                'string',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }
                    $val = trim($value);

                    // Block dangerous URI schemes
                    if (preg_match('/^(javascript|vbscript|data):/i', $val)) {
                        $fail('URL gambar/berkas tidak valid atau menggunakan protokol yang dilarang.');

                        return;
                    }

                    // Strictly reject protocol-relative URLs (e.g. //evil.example or ///evil.example)
                    if (str_starts_with($val, '//')) {
                        $fail('URL gambar/berkas tidak boleh menggunakan protocol-relative URL.');

                        return;
                    }

                    // Positive allowlist:
                    // 1. Registered local paths: /storage/..., /uploads/..., /documents/..., /assets/...
                    $isAllowedLocal = preg_match('/^(\/?(storage|uploads|documents|assets)\/)/i', $val);

                    // 2. Valid external HTTP/HTTPS URLs
                    $isAllowedExternal = filter_var($val, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//i', $val);

                    if (! $isAllowedLocal && ! $isAllowedExternal) {
                        $fail('URL gambar/berkas harus berupa tautan web yang valid (http/https) atau jalur berkas lokal yang terdaftar (/storage/, /uploads/, /documents/, /assets/).');
                    }
                },
            ],
            'image' => [
                'nullable',
                'file',
                'mimes:jpeg,png,jpg,webp,gif,pdf,xls,xlsx,doc,docx',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value instanceof UploadedFile) {
                        $ext = strtolower($value->getClientOriginalExtension());
                        $dangerousExts = ['php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'phps', 'cgi', 'pl', 'py', 'sh', 'bat', 'exe', 'svg', 'htaccess'];
                        if (in_array($ext, $dangerousExts, true)) {
                            $fail('Ekstensi file yang diunggah tidak diizinkan demi alasan keamanan.');

                            return;
                        }

                        $mime = $value->getMimeType();
                        $allowedMimes = [
                            'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ];
                        if (! in_array($mime, $allowedMimes, true)) {
                            $fail('File yang diunggah memiliki tipe MIME asli yang tidak valid.');

                            return;
                        }

                        // Inspect actual file content for executable PHP tags and image integrity
                        $realPath = $value->getRealPath();
                        if ($realPath && file_exists($realPath)) {
                            $contentSample = @file_get_contents($realPath, false, null, 0, 4096);
                            if ($contentSample && (str_contains($contentSample, '<?php') || str_contains($contentSample, '<?='))) {
                                $fail('File yang diunggah mengandung kode skrip yang dilarang.');

                                return;
                            }

                            if (in_array($ext, ['jpeg', 'jpg', 'png', 'webp', 'gif'], true)) {
                                if (@getimagesize($realPath) === false) {
                                    $fail('File gambar tidak valid atau rusak.');

                                    return;
                                }
                            }
                        }
                    }
                },
            ],
            'is_promoted' => ['nullable', 'boolean'],
            'author' => ['nullable', 'string', 'max:255'],
            'publish_date' => ['nullable', 'date'],
        ], $this->contentLinkRules());
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul konten wajib diisi.',
            'status.required' => 'Status konten wajib dipilih.',
            'status.in' => 'Status tidak valid.',
            'image.mimes' => 'File yang diunggah harus berupa gambar (JPEG, PNG, WebP, GIF), PDF, atau dokumen Office.',
            'image.max' => 'Ukuran file maksimal 2 MB agar server tetap cepat dan hemat penyimpanan.',
        ];
    }
}
