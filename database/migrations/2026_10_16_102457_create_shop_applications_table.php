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
        Schema::create('shop_applications', function (Blueprint $table) {
            $table->id();
   $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
           $table->string('shop_name');
            $table->string('slug')->unique();
            $table->string('shop_type');
            $table->string('other_shop_type')->nullable();
            $table->string('business_stage');
            $table->text('description');
            $table->string('business_email')->nullable();
            $table->string('business_phone');
            $table->string('country')->default('Bangladesh');
            $table->string('division');
            $table->string('district');
            $table->string('upazila')->nullable();
            $table->text('full_address');
            $table->string('postal_code')->nullable();

           $table->unsignedInteger('estimated_product_count')->default(1);
            $table->text('product_categories');
            $table->text('business_history')->nullable();
            $table->text('previous_platforms')->nullable();
            $table->text('supplier_information')->nullable();
            $table->text('additional_information')->nullable();

            // Applicant identity evidence
            $table->string('legal_name');
            $table->date('date_of_birth')->nullable();
            $table->string('identity_document_type');
            $table->string('identity_document_number')->nullable();
            $table->string('identity_photo_path');
            $table->string('identity_document_path')->nullable();
            // Consent and review
            $table->boolean('terms_accepted')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();



            $table->timestamps();

              $table->index(['assigned_admin_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_applications');
    }
};
