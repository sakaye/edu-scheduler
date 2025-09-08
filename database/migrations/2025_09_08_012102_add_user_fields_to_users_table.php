<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('student_id')->nullable()->unique();
            $table->string('global_role')->default('student');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->json('preferences')->nullable();
            $table->unsignedBigInteger('current_team_id')->nullable();

            $table->index(['global_role', 'is_active']);
            $table->index('student_id');
            $table->index('last_login_at');
            $table->index('current_team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['global_role', 'is_active']);
            $table->dropIndex(['student_id']);
            $table->dropIndex(['last_login_at']);
            $table->dropIndex(['current_team_id']);

            $table->dropColumn([
                'first_name',
                'last_name', 
                'student_id',
                'global_role',
                'is_active',
                'last_login_at',
                'preferences',
                'current_team_id',
            ]);
        });
    }
};
