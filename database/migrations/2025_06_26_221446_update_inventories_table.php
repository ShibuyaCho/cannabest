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
        Schema::table('inventories', function (Blueprint $table) {
            // Drop unnecessary columns
            $table->dropColumn([
                'original_PackageId',
                'original_name',
                'original_desc',
                'inventory_count',
                'extraName',
                'category',
                'original_productLabel',
                'UnitCbdPercent',
                'UnitThcPercent',
                'Api_Id',
                'PackageType',
                'SourceHarvestNames',
                'SourcePackageLabels',
                'UnitOfMeasureName',
                'UnitOfMeasureAbbreviation',
                'ItemFromFacilityLicenseNumber',
                'ItemFromFacilityName',
                'PackagedDate',
                'ExpirationDate',
                'SellByDate',
                'UseByDate',
                'InitialLabTestingState',
                'LabTestingState',
                'LabTestingStateDate',
                'LabTestingPerformedDate',
                'LabTestResultExpirationDateTime',
                'LabTestingRecordedDate',
                'LabTestStageId',
                'LabTestStage',
                'ProductionBatchNumber',
                'SourceProductionBatchNumbers',
                'ReceivedDateTime',
                'ReceivedFromFacilityLicenseNumber',
                'ReceivedFromFacilityName',
                'LastModified',
                'IsProductionBatch',
                'IsTradeSample',
                'IsTradeSamplePersistent',
                'SourcePackageIsTradeSample',
                'IsDonation',
                'IsDonationPersistent',
                'SourcePackageIsDonation',
                'IsTestingSample',
                'IsProcessValidationTestingSample',
                'ProductRequiresRemediation',
                'ContainsRemediatedProduct',
                'RemediationDate',
                'ProductRequiresDecontamination',
                'ContainsDecontaminatedProduct',
                'DecontaminationDate',
                'ReceivedFromManifestNumber',
                'IsOnHold',
                'IsOnRecall',
                'ArchivedDate',
                'IsFinished',
                'FinishedDate',
                'IsOnTrip',
                'IsOnRetailerDelivery',
                'PackageForProductDestruction',
                'Item',
                'ProductLabel',
                'UnitCbdContent',
                'UnitThcContent',
                'package_id',
                'unit_cbd_percent',
                'unit_thc_percent',
                'unit_volume',
                'unit_weight',
                'serving_size',
                'SalesDateTime',
                'ExternalReceiptNumber',
                'SalesCustomerType',
                'PatientLicenseNumber',
                'CaregiverLicenseNumber',
                'IdentificationMethod',
                'PatientRegistrationLocationId',
                'TotalAmount',
                'UnitThcContentUnitOfMeasure',
                'UnitWeightUnitOfMeasure',
                'InvoiceNumber',
                'ExciseTax',
                'CityTax',
                'CountyTax',
                'MunicipalTax',
            ]);
        });
    }

    public function down()
    {
        Schema::table('inventories', function (Blueprint $table) {
            // Add the columns back if needed (reverse the drop)
            // This is optional and depends on your rollback strategy
        });
    }
};
