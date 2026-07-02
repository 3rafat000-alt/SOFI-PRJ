<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * SEO meta data for the marketing site's public pages.
 */
class PageMeta extends Model
{
    protected $table = 'page_meta';

    protected $fillable = [
        'page_key', 'title', 'title_ar', 'description', 'description_ar',
        'keywords', 'og_title', 'og_description', 'og_image',
        'canonical_url', 'robots', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function forPage(string $key): ?self
    {
        // Cache raw attributes, never the model — a serialized model in a
        // class-unaware store (database/file) deserializes to __PHP_Incomplete_Class.
        $attrs = Cache::remember("page_meta:{$key}", 3600, fn () => static::where('page_key', $key)->first()?->getAttributes());

        return $attrs ? (new static)->setRawAttributes($attrs, true) : null;
    }

    protected static function booted(): void
    {
        static::saved(fn (self $p) => Cache::forget("page_meta:{$p->page_key}"));
    }
}
