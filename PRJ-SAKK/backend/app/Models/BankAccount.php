<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class BankAccount extends Model {
    use SoftDeletes;
    // 🔒 SEC-003: user_id intentionally NOT fillable — set from auth session only.
    // status intentionally NOT fillable — managed through dedicated methods.
    protected $fillable = ['bank_name', 'account_name', 'account_number_encrypted', 'account_number_last4', 'iban', 'swift_code', 'branch_code', 'is_default', 'verification_data'];
    protected $casts = ['is_default' => 'boolean', 'verification_data' => 'json'];
    public function user() { return $this->belongsTo(User::class); }
}
