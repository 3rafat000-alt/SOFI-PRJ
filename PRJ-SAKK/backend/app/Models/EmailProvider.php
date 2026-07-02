<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmailProvider extends Model {
    protected $fillable = ['name', 'provider', 'config', 'from_email', 'from_name', 'is_active', 'is_default'];
    protected $casts = ['config' => 'json', 'is_active' => 'boolean', 'is_default' => 'boolean'];
}
