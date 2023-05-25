<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string("firstname");
            $table->string("lastname");
            $table->string("country")->nullable();
            $table->tinyText("address");
            $table->string("city");
            $table->string("state")->nullable();
            $table->integer("postcode")->nullable();
            $table->string("phone",20);
            $table->string("email");
            $table->decimal("total",14,2);
            $table->string("payment_method",20);
            $table->boolean("is_paid")->default(false);
            $table->tinyInteger("status")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
