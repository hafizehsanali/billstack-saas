<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerPayment extends Model
{
    use BelongsToTenant, SoftDeletes;
    protected $fillable = [
        'tenant_id',
        'customer_id',
        'invoice_id',
        'amount',
        'payment_method',
        'payment_date',
        'notes',
        'reference_no',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

}
