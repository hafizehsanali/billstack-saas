<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'opening_balance',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function returns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    // Total invoice amount
    public function totalSales(): float
    {
        return $this->invoices()
            ->whereIn('status', ['paid', 'partial', 'unpaid'])
            ->sum('total');
    }

    // Total received payments
    public function totalPaid(): float
    {
        return $this->payments()->sum('amount');
    }

    // Amount the customer still owes the business.
    public function remainingAmount(): float
    {
        return $this->totalSales() - $this->totalPaid();
    }
}
