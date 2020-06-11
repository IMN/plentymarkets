<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 6/05/20
 * Time: 19:46
 */

namespace IMN\Migrations;


use IMN\Models\Order;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

class UpdateOrderTable
{
    /**
     * @param Migrate $migrate
     */
    public function run(Migrate $migrate)
    {
        //$migrate->deleteTable(Order::class);
        $migrate->updateTable(Order::class);
    }
}