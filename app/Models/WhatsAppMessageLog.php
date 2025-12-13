<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessageLog extends Model
{
    protected $table = 'whatsapp_message_logs';

    protected $fillable = [
        'order_id',
        'recipient_phone',
        'template_id',
        'template_name',
        'template_vars',
        'message_type',
        'trigger_event',
        'response',
        'status',
        'gupshup_message_id',
        'error_message',
        'sent_by',
    ];

    protected $casts = [
        'template_vars' => 'array',
        'response' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationship with Order
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Relationship with Admin (sender)
    public function sender(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin::class, 'sent_by');
    }

    // Get status badge HTML
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'sent' => '<span class="badge bg-success">Sent</span>',
            'failed' => '<span class="badge bg-danger">Failed</span>',
            'delivered' => '<span class="badge bg-info">Delivered</span>',
            'read' => '<span class="badge bg-primary">Read</span>',
            default => '<span class="badge bg-warning">Pending</span>',
        };
    }

    // Get formatted created date
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('d-M-Y H:i:s');
    }
}