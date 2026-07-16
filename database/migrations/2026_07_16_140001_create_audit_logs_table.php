<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Unlike every other FK in this schema, this is nullOnDelete rather than
            // cascadeOnDelete: an audit trail that deletes itself along with the actor
            // would erase the record of `user.deleted` the moment that admin is removed.
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            // Snapshots, so a row still reads sensibly once the actor or target is gone.
            $table->string('actor_label');
            $table->string('action', 30);
            $table->string('target_type', 20)->nullable();
            $table->string('target_id', 36)->nullable();
            $table->string('target_label')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('action');
            $table->index('created_at');
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
