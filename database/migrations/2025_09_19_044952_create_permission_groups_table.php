<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., User Management
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_groups');
    }
};
