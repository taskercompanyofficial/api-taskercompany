<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('staff');
            $table->string('complain_num');
            $table->string('brand_complaint_no')->nullable();
            $table->string('applicant_name');
            $table->string('applicant_email')->nullable();
            $table->string('applicant_phone');
            $table->string('applicant_whatsapp')->nullable();
            $table->string('extra_numbers')->nullable();
            $table->string('reference_by')->nullable();
            $table->string('applicant_adress');
            $table->string('description')->nullable();
            $table->string('branch_id')->nullable();
            $table->string('brand_id')->nullable();
            $table->string('product')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number_ind')->nullable();
            $table->string('serial_number_oud')->nullable();
            $table->string('mq_nmb')->nullable();
            $table->date('p_date')->nullable();
            $table->date('complete_date')->nullable();
            $table->integer('amount')->nullable();
            $table->string('product_type')->nullable();
            $table->string('technician')->nullable();
            $table->string('helper')->nullable();
            $table->string('driver')->nullable();
            $table->string('status')->default('open');
            $table->string('complaint_type')->nullable();
            $table->text('provided_services')->nullable();
            $table->string('warranty_type')->nullable();
            $table->text('happy_call_remarks')->nullable();
            $table->text('files')->nullable();
            $table->timestamps();
        });
        Schema::create('assigned_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('complaints');
            $table->foreignId('assigned_by')->constrained('staff');
            $table->foreignId('assigned_to')->constrained('staff');
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('status')->default('pending');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('description')->nullable();
            $table->text('remarks')->nullable();
            $table->text('customer_remarks')->nullable();
            $table->integer('rating')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('complaint_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints');
            $table->foreignId('user_id')->constrained('staff');
            $table->string('description')->nullable();
            $table->text('data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('assigned_jobs');
        Schema::dropIfExists('complaint_histories');
    }
};
