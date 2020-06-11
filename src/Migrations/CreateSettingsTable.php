<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 12/03/20
 * Time: 13:15
 */

namespace IMN\Migrations;

use IMN\Models\Settings;
use IMN\Services\SettingsHelper;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;


class CreateSettingsTable
{


    /**
     * @param Migrate $migrate
     */
    public function run(Migrate $migrate)
    {
        $migrate->createTable(Settings::class);
    }

}