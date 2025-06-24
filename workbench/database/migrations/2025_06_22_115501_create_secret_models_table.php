<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secret_models', function (Blueprint $table) {
            $table->id();

            $table->text('encrypted_string')->nullable();
            $table->text('encrypted_boolean')->nullable();
            $table->text('encrypted_date')->nullable();
            $table->text('encrypted_datetime')->nullable();
            $table->text('encrypted_immutable_date')->nullable();
            $table->text('encrypted_immutable_datetime')->nullable();

            $table->string('plain_string')->nullable();
            $table->boolean('plain_boolean')->nullable();
            $table->date('plain_date')->nullable();
            $table->dateTime('plain_datetime')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secret_models');
    }
};
