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
    Schema::create('transactions', function (Blueprint $table) {
        $table->id(); // Unique ID for each transaction record
        
        // We'll assume you have a users table and this links the transaction to a user.
        // If you don't have a users table yet, we can add this later.
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        
        // --- Details for initiating the payment ---
        $table->string('order_reference')->unique(); // Our unique reference for the order (e.g., LOADTEST14584044)
        $table->decimal('amount', 10, 2); // The amount the user needs to pay
        $table->string('currency', 3)->default('NGN'); // The currency (e.g., NGN from the docs)
        
        // --- Details from the payment gateway ---
        $table->string('payment_reference')->nullable()->unique(); // Arca's unique reference (e.g., ARCAORD-1EAC2D3723E611EFBDBC0AD371968847)
        
        // --- Tracking the status ---
        $table->string('status')->default('pending'); // The status of our transaction (e.g., pending, successful, failed)
        $table->text('remarks')->nullable(); // Any remarks or messages from the API
        
        // --- Timestamps ---
        $table->timestamps(); // Creates `created_at` and `updated_at` columns automatically
    });
}
};
