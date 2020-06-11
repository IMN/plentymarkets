<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 16/05/20
 * Time: 20:32
 */

namespace IMN\Helper;


use IMN\Repositories\LogRepository;

class LogHelper
{
    const TYPE_INFO = 'INFO';
    const TYPE_ERROR = 'ERROR';
    const TYPE_DEBUG = 'DEBUG';
    const TYPE_SUCCESS = 'SUCCESS';

    private $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    public function log(
        string $imnOrderId,
        string $type,
        string $message
    ) {
        $merchantCode = "";
        $marketplaceOrderId = "";
        $marketplaceCode = "";
        if(!empty($imnOrderId)) {
            $imnOrderIdParts = explode("/", $imnOrderId);
            if(isset($imnOrderId[2])) {
                $marketplaceCode = $imnOrderIdParts[0];
                $merchantCode = $imnOrderIdParts[1];
                $marketplaceOrderId = $imnOrderIdParts[2];
            }
        }
        $this->logRepository->addLogLine($merchantCode, $marketplaceOrderId, $marketplaceCode, $type, $message);

    }

    public function clear(int $days) : void {
        $this->logRepository->clearLog($days);
    }

}