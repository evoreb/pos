<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountStock extends Model
{
    protected $table = 'count_stock';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'date','user_id', 'warehouse_id','file_stock','category_id'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'warehouse_id' => 'integer',
        'category_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function warehouse()
    {
        return $this->belongsTo('App\Models\Warehouse');
    }

     public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }

}
