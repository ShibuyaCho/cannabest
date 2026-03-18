<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            
            // File/Image field – you might store a file path or filename.
            

            // Basic Product and Package Info
            $table->string('original_PackageId')->nullable();
            $table->string('original_name'); // required field, so not nullable
            $table->string('name'); // hidden duplicate of original_name for required purposes
            $table->text('original_desc')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->decimal('weight', 10, 2)->default(0);
            
            // Discount Tiers and Pricing
            $table->string('selected_discount_tier')->nullable();
            $table->decimal('original_price', 10, 2)->default(0);
            $table->decimal('original_cost', 10, 2)->default(0);

            // Package Quantities and Inventory Type
            $table->decimal('Quantity', 10, 2)->nullable();
            $table->decimal('min_qty', 10, 2)->nullable();
            $table->unsignedBigInteger('storeQty')->default(0);
            $table->enum('inventory_type', ['inventories', 'hold_inventories'])->default('inventories');
            $table->unsignedInteger('inventory_count')->default(0);

            // Extra Package Fields
            $table->string('extraName')->nullable();
            $table->string('description')->nullable();
            $table->string('category')->nullable();
            $table->string('original_productLabel')->nullable();
            $table->string('UnitCbdPercent')->nullable();
            $table->string('UnitThcPercent')->nullable();

            // API Package Fields
            $table->string('Api_Id')->nullable();
            $table->string('Label')->nullable();
            $table->string('PackageType')->nullable();
            $table->string('SourceHarvestNames')->nullable();
            $table->string('SourcePackageLabels')->nullable();
            $table->string('UnitOfMeasureName')->nullable();
            $table->string('UnitOfMeasureAbbreviation')->nullable();
            $table->string('ItemFromFacilityLicenseNumber')->nullable();
            $table->string('ItemFromFacilityName')->nullable();
            $table->date('PackagedDate')->nullable();
            $table->date('ExpirationDate')->nullable();
            $table->date('SellByDate')->nullable();
            $table->date('UseByDate')->nullable();
            $table->string('InitialLabTestingState')->nullable();
            $table->string('LabTestingState')->nullable();
            $table->date('LabTestingStateDate')->nullable();
            $table->dateTime('LabTestingPerformedDate')->nullable();
            $table->dateTime('LabTestResultExpirationDateTime')->nullable();
            $table->dateTime('LabTestingRecordedDate')->nullable();
            $table->string('LabTestStageId')->nullable();
            $table->string('LabTestStage')->nullable();
            $table->string('ProductionBatchNumber')->nullable();
            $table->string('SourceProductionBatchNumbers')->nullable();
            $table->dateTime('ReceivedDateTime')->nullable();
            $table->string('ReceivedFromFacilityLicenseNumber')->nullable();
            $table->string('ReceivedFromFacilityName')->nullable();
            $table->dateTime('LastModified')->nullable();

            // Additional API Package fields from getPackageInfo
            $table->boolean('IsProductionBatch')->default(false);
            $table->boolean('IsTradeSample')->default(false);
            $table->boolean('IsTradeSamplePersistent')->default(false);
            $table->boolean('SourcePackageIsTradeSample')->default(false);
            $table->boolean('IsDonation')->default(false);
            $table->boolean('IsDonationPersistent')->default(false);
            $table->boolean('SourcePackageIsDonation')->default(false);
            $table->boolean('IsTestingSample')->default(false);
            $table->boolean('IsProcessValidationTestingSample')->default(false);
            $table->boolean('ProductRequiresRemediation')->default(false);
            $table->boolean('ContainsRemediatedProduct')->default(false);
            $table->date('RemediationDate')->nullable();
            $table->boolean('ProductRequiresDecontamination')->default(false);
            $table->boolean('ContainsDecontaminatedProduct')->default(false);
            $table->date('DecontaminationDate')->nullable();
            $table->string('ReceivedFromManifestNumber')->nullable();
            $table->boolean('IsOnHold')->default(false);
            $table->string('IsOnRecall')->nullable();
            $table->date('ArchivedDate')->nullable();
            $table->boolean('IsFinished')->default(false);
            $table->date('FinishedDate')->nullable();
            $table->boolean('IsOnTrip')->default(false);
            $table->boolean('IsOnRetailerDelivery')->default(false);
            $table->string('PackageForProductDestruction')->nullable();

            // Optionally store nested JSON data as text (or use JSON column type if supported by your database)
            $table->json('Item')->nullable();
            $table->json('ProductLabel')->nullable();

            // Timestamps for created and updated times
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventories');
    }
};

