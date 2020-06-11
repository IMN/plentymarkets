<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 12/03/20
 * Time: 13:17
 */
namespace IMN\Contracts;


use IMN\Models\Order;
use IMN\Models\Settings;

interface OrderRepositoryContract
{

//    public function addOrder(array $data): Order;


//    public function getOrderByPlentyId($plentyOrderId);

    public function getOrder($marketplaceCode, $merchantCode, $marketplaceOrderId);

}