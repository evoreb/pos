<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentPurchaseReturns extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'purchase_return_id', 'date', 'montant','change', 'Ref', 'payment_method_id', 'user_id', 'notes','account_id'
    ];

    protected $casts = [
        'montant' => 'double',
        'change'  => 'double',
        'purchase_return_id' => 'integer',
        'user_id' => 'integer',
        'account_id' => 'integer',
        'payment_method_id' => 'integer',
    ];


    public function payment_method()
    {
        return $this->belongsTo('App\Models\PaymentMethod');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function account()
    {
        return $this->belongsTo('App\Models\Account');
    }

    public function PurchaseReturn()
    {
        return $this->belongsTo('App\Models\PurchaseReturn');
    }

}
