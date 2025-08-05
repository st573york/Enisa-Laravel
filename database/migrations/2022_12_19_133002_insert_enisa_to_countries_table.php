<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Country;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Country::create([
            'name' => config('constants.USER_GROUP'), 
            'code'=> config('constants.USER_GROUP'), 
            'flag_src' => ''
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Country::where('name', config('constants.USER_GROUP'))->delete();
    }
};
