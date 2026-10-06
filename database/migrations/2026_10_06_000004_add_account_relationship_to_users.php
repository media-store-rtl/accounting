<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // account_id already exists in the current users schema. This migration
        // intentionally remains a no-op so existing deployments are not altered twice.
    }

    public function down(): void
    {
        // Intentionally empty.
    }
};
