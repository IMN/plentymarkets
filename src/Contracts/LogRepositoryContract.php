<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 12/03/20
 * Time: 13:17
 */
namespace IMN\Contracts;


use IMN\Models\Log;
use IMN\Models\Order;
use IMN\Models\Settings;

interface LogRepositoryContract
{



    public function getLog(
        int $page,
        int $itemsPerPage,
        array $filters,
        string $sortBy,
        string $sortOrder
    ) : array;


    public function addLogLine(
        string $merchantCode,
        string $marketplaceOrderId,
        string $marketplaceCode,
        string $type,
        string $message
    ) : Log;

    public function clearLog(int $days) : void;


}