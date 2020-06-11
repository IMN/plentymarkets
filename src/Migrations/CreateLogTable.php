<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 12/03/20
 * Time: 13:15
 */

namespace IMN\Migrations;


use IMN\Models\Log;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;


class CreateLogTable
{


    /**
     * @param Migrate $migrate
     */
    public function run(Migrate $migrate)
    {
        $migrate->createTable(Log::class);
    }

}