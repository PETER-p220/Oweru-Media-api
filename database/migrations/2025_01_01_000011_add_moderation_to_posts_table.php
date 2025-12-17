<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('description');
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            $table->text('moderation_note')->nullable()->after('moderated_by');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn(['status', 'moderation_note']);
        });
    }
};


