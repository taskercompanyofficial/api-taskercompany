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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_room_id')->constrained('chat_rooms');
            $table->enum('sender_type', ['user', 'applicant']);
            $table->string('sender_id'); // Can be user_id or complaint_id/whatsapp number
            $table->text('message');
            $table->string('message_status')->default('sent'); // For tracking delivery/read status
            $table->string('platform')->default('whatsapp'); // Platform message was sent through
            $table->string('whatsapp_message_id')->nullable(); // To store WhatsApp message ID
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
