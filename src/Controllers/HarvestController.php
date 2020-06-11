<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 7/05/20
 * Time: 12:22
 */

namespace IMN\Controllers;

use Plenty\Plugin\Http\Request;
use IMN\Services\HarvestService;
use Plenty\Plugin\Controller;

class HarvestController extends Controller
{

    private $harvestService;

    public function __construct(HarvestService $harvestService)
    {
        $this->harvestService = $harvestService;
    }


    public function harvestOrder(Request $request) {
        $requestData = $request->all();
        if(!array_key_exists('url', $requestData)) {
            throw new \Exception("Key url is not in the request");
        }

        $orderId = $this->harvestService->createOrderFomGoLink($requestData['url']);
        if(!$orderId) {
            if(!empty($this->harvestService->errors)) {
                throw new \Exception(implode("<br>", $this->harvestService->errors));
            }
            throw new \Exception("Some error ocurred");
        }

        return \json_encode(array('success' => true, 'order_id' => $orderId));
    }


    public function harvestOrders() {
        $this->harvestService->synchronizeOrders();
        return \json_encode(array('success' => true));
    }


}