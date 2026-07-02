<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class SupportTicket extends Model {
    use SoftDeletes;
    protected $fillable = ['uuid', 'user_id', 'ticket_number', 'subject', 'description', 'category', 'priority', 'status', 'assigned_to', 'related_transaction', 'resolved_at', 'telegram_chat_id'];
    protected $casts = ['resolved_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
    public function messages() { return $this->hasMany(TicketMessage::class, 'ticket_id'); }
    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to'); }
}
