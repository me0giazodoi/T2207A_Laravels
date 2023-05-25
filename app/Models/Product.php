<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = "products";
    protected $fillable = [
        "name",
        "slug",
        "price",
        "thumbnail",
        "qty",
        "description",
        "category_id",
    ];

    public function orders(){
        return $this->belongsToMany(Order::class,"order_products")
            ->withPivot("buy_qty","price");
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }
}
