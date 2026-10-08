<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  if(!Schema::hasTable('cost_calculations')) Schema::create('cost_calculations',function(Blueprint $t){$t->id();$t->foreignId('company_id')->constrained()->cascadeOnDelete();$t->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();$t->foreignId('order_id')->nullable()->constrained()->nullOnDelete();$t->foreignId('goods_id')->nullable()->constrained()->nullOnDelete();$t->string('status',30)->default('calculated');$t->decimal('material_cost',20,4)->default(0);$t->decimal('labor_cost',20,4)->default(0);$t->decimal('scrap_cost',20,4)->default(0);$t->decimal('direct_cost',20,4)->default(0);$t->decimal('total_cost',20,4)->default(0);$t->timestamp('calculated_at');$t->foreignId('calculated_by_user_id')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();$t->index(['company_id','fiscal_year_id','order_id','goods_id']);});
  if(!Schema::hasTable('cost_components')) Schema::create('cost_components',function(Blueprint $t){$t->id();$t->foreignId('cost_calculation_id')->constrained('cost_calculations')->cascadeOnDelete();$t->string('component_type',40);$t->string('source_type',80)->nullable();$t->unsignedBigInteger('source_id')->nullable();$t->decimal('amount',20,4);$t->json('metadata')->nullable();$t->timestamps();$t->index(['cost_calculation_id','component_type']);$t->index(['source_type','source_id']);});
 }
 public function down(): void {Schema::dropIfExists('cost_components');Schema::dropIfExists('cost_calculations');}
};