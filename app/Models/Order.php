<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'total_amount',
        'shipping_address',
        'shipping_phone',
        'shipping_name',
        'payment_method',
        'payment_status',
        'note',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => '待處理',
            'processing' => '處理中',
            'shipped' => '已出貨',
            'delivered' => '已送達',
            'cancelled' => '已取消',
            default => '未知狀態',
        };
    }

    public function getPaymentStatusTextAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => '待付款',
            'paid' => '已付款',
            'failed' => '付款失敗',
            'refunded' => '已退款',
            default => '未知狀態',
        };
    }
} 