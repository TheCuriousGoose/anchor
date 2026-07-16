<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 10);
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();

            // One open invitation per person per board; re-inviting updates the existing row.
            $table->unique(['board_id', 'email']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_invitations');
    }
};
