<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteStat extends Model
{
    protected $table = 'site_stats';

    protected $fillable = [
        'happy_clients',
        'properties_listed',
        'agents_count',
        'satisfaction_pct',
    ];

    protected $casts = [
        'happy_clients'     => 'integer',
        'properties_listed' => 'integer',
        'agents_count'      => 'integer',
        'satisfaction_pct'  => 'integer',
    ];
}
