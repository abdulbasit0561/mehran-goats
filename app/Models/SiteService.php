<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteService extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'icon', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Colour palette keyed by the `icon` column.
     * Each entry provides Tailwind classes for the icon container
     * and the icon text colour.
     */
    public static array $iconPalette = [
        'emerald' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-600'],
        'blue'    => ['bg' => 'bg-blue-100',    'text' => 'text-blue-600'],
        'teal'    => ['bg' => 'bg-teal-100',    'text' => 'text-teal-600'],
        'amber'   => ['bg' => 'bg-amber-100',   'text' => 'text-amber-600'],
        'rose'    => ['bg' => 'bg-rose-100',    'text' => 'text-rose-600'],
        'indigo'  => ['bg' => 'bg-indigo-100',  'text' => 'text-indigo-600'],
    ];

    /**
     * SVG path data keyed by the `icon` column.
     * These match the original six service icons on the landing page.
     */
    public static array $iconSvgs = [
        'emerald' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.701 2.701 0 00-1.5-.454M9 6v2m3-2v2m3-2v2M9 3h.01M12 3h.01M15 3h.01M21 21v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7h18zm-3-9v-2a2 2 0 00-2-2H8a2 2 0 00-2 2v2h12z" />',
        'blue'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />',
        'teal'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />',
        'amber'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />',
        'rose'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />',
        'indigo'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />',
    ];

    /**
     * Scope: only active services.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: ordered by sort_order then id.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Get the Tailwind classes for this service's icon.
     */
    public function getIconClassesAttribute(): array
    {
        return self::$iconPalette[$this->icon] ?? self::$iconPalette['emerald'];
    }

    /**
     * Get the SVG path data for this service's icon.
     */
    public function getIconSvgAttribute(): string
    {
        return self::$iconSvgs[$this->icon] ?? self::$iconSvgs['emerald'];
    }
}
