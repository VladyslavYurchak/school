<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    public const MONOPAY_INVOICE_VALIDITY_SECONDS = 3600;

    protected $fillable = [
        'student_id',
        'amount',
        'currency',
        'status',
        'type',
        'provider',
        'provider_payment_id',
        'provider_order_id',
        'description',
        'paid_at',
        'payload',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'payload' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(StudentSubscription::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function hasMonoPayInvoice(): bool
    {
        $payload = is_array($this->payload) ? $this->payload : [];

        return (bool) (
            $this->provider_payment_id
            || data_get($payload, 'mono_invoice.invoiceId')
            || data_get($payload, 'mono_invoice.pageUrl')
        );
    }

    public function hasReusableMonoPayInvoice(): bool
    {
        return !$this->hasMonoPayInvoice()
            || $this->updated_at->gt(now()->subSeconds(self::MONOPAY_INVOICE_VALIDITY_SECONDS));
    }

    public function failExpiredMonoPayInvoice(): void
    {
        $payload = is_array($this->payload) ? $this->payload : [];

        $this->update([
            'status' => 'failed',
            'payload' => array_merge($payload, [
                'expired_locally' => true,
                'expired_locally_at' => now()->toISOString(),
            ]),
        ]);
    }
}
