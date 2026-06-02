<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
     use BelongsToTenant, SoftDeletes;
    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'email',
        'address',
        'opening_balance'
    ];
    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function totalPurchases(): float
    {
        return $this->purchases()
            ->where('status', '!=', 'cancelled')
            ->sum('total');
    }

    public function totalPaid(): float
    {
        return $this->payments()->sum('amount');
    }

    public function remainingAmount(): float
    {
        return $this->purchases()
            ->where('status', '!=', 'cancelled')
            ->sum('remaining_amount');
    }
}
