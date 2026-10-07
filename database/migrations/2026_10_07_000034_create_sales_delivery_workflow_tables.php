<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_production',function(Blueprint $t){
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('production_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->primary(['order_id','production_id']);
        });
        Schema::create('delivery_requests',function(Blueprint $t){
            $t->id();$t->foreignId('company_id')->constrained()->cascadeOnDelete();$t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained()->restrictOnDelete();$t->foreignId('requested_by_user_id')->constrained('users')->restrictOnDelete();
            $t->timestamp('requested_at');$t->timestamp('scheduled_at');$t->string('vehicle')->nullable();$t->string('recipient_name');
            $t->string('status',30)->default('pending');$t->text('notes')->nullable();$t->timestamps();$t->index(['company_id','status']);
        });
        Schema::create('delivery_request_items',function(Blueprint $t){
            $t->id();$t->foreignId('delivery_request_id')->constrained()->cascadeOnDelete();$t->foreignId('goods_id')->constrained()->restrictOnDelete();
            $t->decimal('quantity',18,4);$t->timestamps();$t->unique(['delivery_request_id','goods_id']);
        });
        Schema::create('deliveries',function(Blueprint $t){
            $t->id();$t->foreignId('company_id')->constrained()->cascadeOnDelete();$t->foreignId('delivery_request_id')->unique()->constrained()->cascadeOnDelete();
            $t->foreignId('issued_by_user_id')->constrained('users')->restrictOnDelete();$t->timestamp('issued_at');
            $t->string('status',30)->default('issued');$t->timestamp('handed_over_at')->nullable();$t->foreignId('handed_over_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('received_by')->nullable();$t->text('handover_note')->nullable();$t->json('metadata')->nullable();$t->timestamps();$t->index(['company_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');Schema::dropIfExists('delivery_request_items');Schema::dropIfExists('delivery_requests');Schema::dropIfExists('order_production');
    }
};