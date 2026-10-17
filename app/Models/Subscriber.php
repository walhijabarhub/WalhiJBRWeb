<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Subscriber extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'email',
        'token',
        'is_active',
        'confirmed_at',
    ];

    protected $hidden = [
        'token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Subscriber $subscriber) {
            $subscriber->token ??= Str::random(64);
        });
    }

    /**
     * Confirmed subscribers who have not unsubscribed.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNotNull('confirmed_at');
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function confirmationUrl(): string
    {
        return URL::temporarySignedRoute('newsletter.confirm', now()->addDays(7), ['subscriber' => $this->id]);
    }

    public function unsubscribeUrl(): string
    {
        return route('newsletter.unsubscribe', ['token' => $this->token]);
    }
}
