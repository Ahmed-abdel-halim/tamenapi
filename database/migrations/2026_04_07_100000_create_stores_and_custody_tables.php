<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Store Items (Paper-based, Electronic, Furniture, etc.)
        Schema::create('store_items', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'Insurance Booklet - Third Party'
            $table->string('serial_prefix')->nullable(); // e.g., 'INS-'
            $table->enum('category', ['paper', 'electronic', 'furniture', 'other'])->default('paper');
            $table->string('unit')->default('piece'); // 'booklet', 'unit', etc.
            $table->text('description')->nullable();
            $table->integer('min_threshold')->default(5); // Alert when stock is low
            $table->timestamps();
        });

        // 2. Inventory (Main Store Stock)
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('store_items')->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->string('warehouse_location')->nullable();
            $table->timestamps();
        });

        // 3. Fixed Custody (Items assigned to Agents/Employees)
        Schema::create('fixed_custodies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('store_items')->onDelete('cascade');
            $table->integer('quantity');
            $table->string('serial_start')->nullable(); // For booklets/electronic devices
            $table->string('serial_end')->nullable();
            $table->morphs('recipient'); // Can be 'User' (employee) or 'BranchAgent' (agent)
            $table->date('assigned_at');
            $table->date('return_due_at')->nullable();
            $table->enum('condition', ['new', 'used', 'damaged'])->default('new');
            $table->enum('status', ['active', 'returned', 'lost', 'damaged'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Custody Movements (History)
        Schema::create('custody_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('store_items')->onDelete('cascade');
            $table->morphs('recipient');
            $table->integer('quantity');
            $table->enum('type', ['issue', 'return', 'loss', 'damage']);
            $table->foreignId('processed_by')->constrained('users'); // Finance employee who did this
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custody_movements');
        Schema::dropIfExists('fixed_custodies');
        Schema::dropIfExists('inventory_stocks');
        Schema::dropIfExists('store_items');
    }
};
