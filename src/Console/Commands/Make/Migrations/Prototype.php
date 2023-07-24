<?php
namespace App\Http\Middleware;

use Clicalmani\Flesco\Database\Factory\Factory;
use Clicalmani\Flesco\Database\Factory\Maker;
use Clicalmani\Flesco\Database\Factory\Schema;

return new class extends Factory {

    function create() {

        Schema::create('table_name', function(Maker $table) {

            // Write your schema here
            // Example :
            // $table->column('id')->int()->insigned()->nullable(false)->autoIncrement();
        });
    }
};