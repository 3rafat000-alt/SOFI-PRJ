<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSub extends Model
{
    protected $table = 'newsletter_subs';

    protected $fillable = [
        'email',
        'locale',
    ];
}
