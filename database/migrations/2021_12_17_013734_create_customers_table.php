<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('refid')->nullable();
            $table->string('street')->nullable();
            $table->string('barangay_name')->nullable();
            $table->string('municipality_name')->nullable();
            $table->string('province_name')->nullable();
            $table->string('region')->nullable();
            $table->string('island')->nullable();
            $table->unsignedBigInteger('customer_import_id')->nullable();
            $table->string('source_db')->nullable();
            $table->string('source_table')->nullable();
            $table->unsignedBigInteger('source_index')->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->json('geocoder_data')->nullable();
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
        Schema::dropIfExists('customers');
    }
}
