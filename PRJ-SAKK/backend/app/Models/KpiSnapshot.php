<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class KpiSnapshot extends Model {
    protected $fillable = ['kpi_name', 'value', 'threshold_green', 'threshold_yellow', 'threshold_red', 'computed_at', 'source', 'owner_id'];
    protected $casts = ['value' => 'decimal:8', 'threshold_green' => 'decimal:8', 'threshold_yellow' => 'decimal:8', 'threshold_red' => 'decimal:8', 'computed_at' => 'datetime'];
    protected $keyType = 'string';
    public $incrementing = false;
    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
}
