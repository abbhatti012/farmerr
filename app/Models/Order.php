<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'shopify_order_id',
        'order_date',
        'order_number',
        'tags',
        'note',
        'note_attributes',
        'email',
        'phone',
        'total_price',
        'subtotal_price',
        'total_tax',
        'financial_status',
        'fulfillment_status',
        'currency',
        'buyer_accepts_marketing',
        'confirmed',
        'total_discounts',
        'total_line_items_price',
        'contact_email',
        'zoho_invoice',
        'order_status_url',
        'total_shipping_price',
        'zoho_status',
        'credit_note_status',
        'occasion',
        'delivery_date',
        'gift_message'
    ];

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }
    public function noteAttributes()
    {
        return $this->hasOne(NoteAttributes::class);
    }

    public function shippingAddress()
    {
        return $this->hasOne(ShippingAddress::class);
    }

    public function billingAddress()
    {
        return $this->hasOne(BillingAddress::class);
    }

    public function lineItems()
    {
        return $this->hasMany(LineItem::class);
    }

    public function shippingLines()
    {
        return $this->hasOne(ShippingLine::class);
    }

    public function discountCodes()
    {
        return $this->hasMany(DiscountCode::class);
    }
    public function whatsappLogs()
    {
        return $this->hasMany(WhatsAppMessageLog::class, 'order_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get the latest WhatsApp message log
     */
    public function latestWhatsappLog()
    {
        return $this->hasOne(WhatsAppMessageLog::class, 'order_id')->latestOfMany();
    }
}
