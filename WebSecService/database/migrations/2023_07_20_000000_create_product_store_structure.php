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
        // Create products table if it doesn't exist
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32);
                $table->string('name', 128);
                $table->decimal('price', 10, 2);
                $table->string('model', 256);
                $table->text('description');
                $table->string('photo')->nullable();
                $table->integer('stock_quantity')->default(0);
                $table->timestamps();
            });
        } else if (!Schema::hasColumn('products', 'stock_quantity')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('stock_quantity')->default(0);
            });
        }

        // Add credit field to users table if it doesn't exist
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'credit')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('credit', 10, 2)->default(0.00);
            });
        }

        // Create purchases table if it doesn't exist
        if (!Schema::hasTable('purchases')) {
            Schema::create('purchases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->decimal('price_paid', 10, 2);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
        
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'stock_quantity')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('stock_quantity');
            });
        }
        
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'credit')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('credit');
            });
        }
    }
}; 