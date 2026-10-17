<?php

namespace App\Http\Requests\Comment;

use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    private bool $spamDetected = false;

    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Move honeypot detection into the request lifecycle.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('extra_phone')) {
            $this->spamDetected = true;
        }
    }

    public function rules(): array
    {
        return array_merge([
            'author_name' => ['required', 'string', 'max:255'],
            'author_email' => ['required', 'email:rfc', 'max:255'],
            'body' => ['required', 'string', 'min:5', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ], Turnstile::rules());
    }

    public function messages(): array
    {
        return [
            'author_name.required' => 'Nama wajib diisi.',
            'author_email.required' => 'Email wajib diisi.',
            'author_email.email' => 'Format email tidak valid.',
            'body.required' => 'Isi komentar wajib diisi.',
            'body.min' => 'Komentar minimal 5 karakter.',
            'body.max' => 'Komentar maksimal 5.000 karakter.',
            'parent_id.exists' => 'Komentar yang dibalas tidak ditemukan.',
            Turnstile::FIELD.'.required' => Turnstile::message(),
        ];
    }

    /**
     * Check if flagged as spam by honeypot.
     */
    public function isSpam(): bool
    {
        return $this->spamDetected;
    }
}
