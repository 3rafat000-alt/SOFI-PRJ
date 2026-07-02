<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SmsProvider extends Model {
    protected $fillable = ['name', 'provider', 'config', 'sender_id', 'is_active', 'is_default', 'priority'];
    protected $casts = ['config' => 'json', 'is_active' => 'boolean', 'is_default' => 'boolean'];
}
