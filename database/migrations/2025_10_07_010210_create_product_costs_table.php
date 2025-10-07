<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_costs', function (Blueprint $table) {
            $table->id();
            $table->string('product_uid')->unique();
            $table->string('product_name');
            $table->decimal('cost', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('product_uid');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_costs');
    }
};
