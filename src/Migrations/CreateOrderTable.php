<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 12/03/20
 * Time: 13:15
 */

namespace IMN\Migrations;

use IMN\Models\Order;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;


class CreateOrderTable
{


    /**
     * @param Migrate $migrate
     */
    public function run(Migrate $migrate)
    {
        //$migrate->deleteTable(Order::class);
        $migrate->createTable(Order::class);
    }

}