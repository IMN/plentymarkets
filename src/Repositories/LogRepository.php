<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 29/04/20
 * Time: 17:50
 */

namespace IMN\Repositories;



use IMN\Contracts\LogRepositoryContract;
use IMN\Models\Log;
use IMN\Models\Order;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

class LogRepository implements LogRepositoryContract
{


    public function getLog(
        int $page,
        int $itemsPerPage,
        array $filters,
        string $sortBy,
        string $sortOrder
    ) : array {

        $filterKeys = array(
            'marketplaceCode',
            'marketplaceOrderId',
            'merchantCode',
            'type',
            'message',
        );

        /**
         * @var $database Database
         */
        $database = pluginApp(DataBase::class);

        $query =  $database->query(Log::class);

        foreach($filters as $key => $filter) {
            if(!in_array($key, $filterKeys)) {
                continue;
            }
            $query->where($key, '=', $filter);
        }

        $entries = $query
            ->orderBy($sortBy, $sortOrder)
            ->forPage($page, $itemsPerPage);

        $entries = $entries->get();
        $totalCount = $database->query(Log::class)->getCountForPagination();
        $result = [
            'page' => $page,
            'isLastPage' => $page == ceil($totalCount/$itemsPerPage),
            'lastPageNumber' => ceil($totalCount/$itemsPerPage),
            'firstOnPage' => 1,
            'lastOnPage' => 2,
            'itemsPerPage' => $itemsPerPage,
            'totalsCount' => $totalCount,
            'entries' => $entries
        ];
        return $result;

    }






    public function addLogLine(
        string $merchantCode,
        string $marketplaceOrderId,
        string $marketplaceCode,
        string $type,
        string $message
    ): Log
    {
        /**
         * @var $database DataBase
         */
        $database = pluginApp(DataBase::class);
        /**
         * @var $log Log
         */
        $log = pluginApp(Log::class);
        $log->merchantCode = $merchantCode;
        $log->marketplaceCode = $marketplaceCode;
        $log->marketplaceOrderId = $marketplaceOrderId;
        $log->type = $type;
        $log->message = $message;
        $log->dateAdd = time();
        $database->save($log);
        return $log;
    }

    public function clearLog(int $days): void
    {
        /**
         * @var $database Database
         */
        $database = pluginApp(DataBase::class);

        $daysStr = "-".$days." days";
        $timestamp = strtotime($daysStr, time());
        $database->query(Log::class)
            ->where('dateAdd', '<', $timestamp)
            ->delete();
    }
}