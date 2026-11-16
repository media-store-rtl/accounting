<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farnam_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 30);
            $table->string('business_name', 180)->nullable();
            $table->string('business_type', 80);
            $table->string('service_interest', 100);
            $table->text('message')->nullable();
            $table->string('status', 24)->default('new')->index();
            $table->string('source', 40)->default('website');
            $table->timestamp('consent_at');
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farnam_leads');
    }
};
