<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('category'); // rentals, property_sales, construction_property_management, lands_and_plots, property_services, investment
            $table->enum('post_type', ['Static', 'Carousel', 'Reel']);
            $table->string('title');
            $table->text('description');
            $table->boolean('ai_generated')->default(false);
            $table->json('metadata')->nullable();
            $table->integer('performance_score')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};

