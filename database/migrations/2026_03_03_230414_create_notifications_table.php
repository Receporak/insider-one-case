<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\table;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('batch_id')->nullable()->index();
                $table->string('recipient');
                $table->enum('channel', ['sms', 'email', 'push']);
                $table->enum('priority', ['high', 'normal', 'low'])->default('normal');
                $table->uuid('template_id')->nullable();
                $table->string('content')->nullable();
                $table->enum('status',['pending', 'sent', 'failed'])->default('pending');
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
        
        Schema::create('notification_batches', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->enum('status',['pending', 'processing', 'completed', 'failed','cancelled'])->default('pending');
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
        
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name');
            $table->enum('channel', ['email', 'sms', 'push']);
            $table->text('content');
            $table->enum('status',['active', 'inactive'])->default('active');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });

        // template_id NULL olan kayıtlar için partial unique index
        DB::statement("
            CREATE UNIQUE INDEX uq_notifications_idempotency
            ON notifications (channel, recipient, template_id)
            WHERE template_id IS NOT NULL
        ");

        // template_id NULL (content tabanlı) kayıtlar için
        DB::statement("
            CREATE UNIQUE INDEX uq_notifications_content_idempotency
            ON notifications (channel, recipient, md5(content))
            WHERE template_id IS NULL AND content IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS uq_notifications_idempotency');
        DB::statement('DROP INDEX IF EXISTS uq_notifications_content_idempotency');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_batches');
        Schema::dropIfExists('notification_templates');
    }
};
