<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 29/04/20
 * Time: 11:11
 */

namespace IMN\Services;


use IMN\Helper\LogHelper;
use IMN\Helper\ProductHelper;
use IMN\Helper\SettingsHelper;
use IMN\Models\Order;
use IMN\Repositories\OrderRepository;
use IMN\Repositories\SettingsRepository;
use IMN\Services\Api\ImnClient;
use Plenty\Exceptions\ValidationException;

class HarvestService
{

    /**
     * @var ImnClient
     */
    private $imnClient;
    private $imnOrderRepository;
    private $settings;
    private $apiStatusOk = false;
    private $imnOrderId = "";

    private $settingsRepository;

    /**
     * @var ProductHelper
     */
    private $productService;

    public $errors = [];


    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var LogHelper
     */
    private $logHelper;

    public function __construct(
        ImnClient $imnClient,
        SettingsHelper $settingsService,
        SettingsRepository $settingsRepository,
        OrderRepository $imnOrderRepository,
        OrderService $orderService,
        ProductHelper $productService,
        LogHelper $logHelper
    )
    {
        $this->logHelper = $logHelper;
        $this->settingsRepository = $settingsRepository;
        $this->orderService = $orderService;
        $this->productService = $productService;
        $this->imnOrderRepository = $imnOrderRepository;
        $this->settings = $settingsService->getProperties();
        $this->imnClient = $imnClient;
        $this->imnClient->init($this->settings['apiToken']['value'], $this->settings['merchantCode']['value']);
        $this->apiStatusOk = $this->imnClient->isCredentialOk();
        if(!$this->apiStatusOk) {
            throw new \Exception("Api credentials are not correct");
        }

        $this->orderService->setSettings($this->settings);
        $this->productService->setSettings($this->settings);
        $this->logHelper->clear(7);
    }



    public function synchronizeOrders($page = 1) {
        //$dateStart->setTimezone(new \DateTimeZone('UTC'));
        $orderList = $this->imnClient->getOrderList(
            100,
            $page,
            $this->settings['lastSyncTime']['value'],
            gmdate('Y-m-d H:i:s')
        );

        if(!$orderList) {
            //@log: there are no orders to import
            throw new \Exception("There are no orders from date " .$this->settings['lastSyncTime']['value']);
            return;
        }


        $entryCount = $orderList['paginationResult']['entryCount'];
        $pageCount = $orderList['paginationResult']['pageCount'];
        $totalEntryCount = $orderList['paginationResult']['totalEntryCount'];
        foreach($orderList['orders'] as $order) {
            $this->synchronizeOrder($order);
        }

        if($pageCount > $page) {
            $page++;
            $this->synchronizeOrders($page);
        }

        $this->settingsRepository->updateSettings('lastSyncTime', array(
            'name' => 'lastSyncTime',
            'value' => gmdate('Y-m-d H:i:s')
        ));

    }




    private function synchronizeOrder($imnOrder) {
        $info = $imnOrder['info'];
        $identifier = $info['identifier'];
        $this->imnOrderId = $identifier['marketplaceCode']."/".$identifier['merchantCode']."/".$identifier['marketplaceOrderId'];
        try {
            $this->logHelper->log($this->imnOrderId, LogHelper::TYPE_INFO, 'Getting products');
            $products = $this->productService->getProductsFromImnOrder($imnOrder, $this->settings['productMap']['value']);
            $statusId = $this->getPlentyOrderStatusId($info['generalInfo']['imnOrderStatus']);
            if(!$statusId) {
                $this->errors[] = "Status map failed for status: ".$info['generalInfo']['imnOrderStatus'];
                throw new \Exception("Status map failed for status: ".$info['generalInfo']['imnOrderStatus']);
            }


            $order = $this->orderService->externalOrderIdExists($this->imnOrderId);
            if(!$order) {
                $order = $this->orderService->createOrder($imnOrder, $statusId, $products);
                $this->imnClient->setMerchantOrderInfo($identifier['marketplaceCode'], $identifier['marketplaceOrderId'], $order->id, 'PlentyMarkets', '1');
                $this->imnOrderRepository->createOrder($order->id, $imnOrder);
                $this->logHelper->log($this->imnOrderId, LogHelper::TYPE_SUCCESS, 'Order created succesfully');
                return $order;
            }


            /**
             * @var $imnDbOrder Order
             */
            $imnDbOrder = $this->imnOrderRepository->getOrder(
                $info['identifier']['marketplaceCode'],
                $info['identifier']['merchantCode'],
                $info['identifier']['marketplaceOrderId']
            );

            if(!$imnDbOrder) {
                $this->imnOrderRepository->createOrder($order->id, $imnOrder);

                $this->logHelper->log($this->imnOrderId, LogHelper::TYPE_INFO, "Order exists but relation with IMN has not been added");
                return false;
            }
            if($imnDbOrder->etag == $info['imnInfo']['etag']) {
                $this->errors[] = "Etag has not changed";
                throw new \Exception("Etag has not changed");
            }
            $order = $this->orderService->updateOrder($order, $imnOrder, $statusId);
            $this->imnOrderRepository->updateOrder($imnDbOrder, $imnOrder);
            $this->logHelper->log($this->imnOrderId, LogHelper::TYPE_SUCCESS, 'Order updated succesfully');

            return $order;

        } catch(ValidationException $ex) {
            $error = $ex->getMessageBag()->toJson();
            $this->logHelper->log($this->imnOrderId, LogHelper::TYPE_ERROR, $error);
            throw $ex;

        } catch(\Exception $ex) {
            $this->logHelper->log($this->imnOrderId, LogHelper::TYPE_ERROR, $ex->getMessage());
        }

        return false;



    }

    public function createOrderFomGoLink($goLink) {
        if(!preg_match("/^https:\/\/go.imn.io\/(index.html#!\/app\/[\w\d]+\/)?orders\//",$goLink)) {
            throw new \Exception("Go link is incorrect");
        }
        $marketplaceCodePos = 0;
        $merchantCode = 1;
        if(preg_match('/\/orders\/order\//', $goLink)) {
            $marketplaceCodePos = 1;
            $merchantCode = 0;
        }
        $url = str_replace("/orders/order", "", $goLink);
        $urlSplit = explode("/", $url);
        $urlSplit = array_slice($urlSplit, -3, 3, true);
        $urlRes = array();
        foreach($urlSplit as $split) {
            $urlRes[] = $split;
        }
        $marketplaceCode = $urlRes[$marketplaceCodePos];
        $merchantCode = $urlRes[$merchantCode];
        $marketplaceId = $urlRes[2];

        $order = $this->imnClient->getOrder($marketplaceCode, $marketplaceId, $merchantCode);

        if(empty($order)) {
            throw new \Exception("Order does not exist");
        }

        $order = $this->synchronizeOrder($order);

        return ($order) ? $order->id : false;
    }



    private function getPlentyOrderStatusId($imnOrderStatus) {
        $statusId = false;
        switch($imnOrderStatus) {
            case 'InProgress':
                $statusId = $this->settings['statusInProgress']['value'];
                break;
            case 'New':
                $statusId = $this->settings['statusNew']['value'];
                break;
            case 'Shipped':
                $statusId = $this->settings['statusShipped']['value'];
                break;
            case 'Cancelled':
                $statusId = $this->settings['statusCancelled']['value'];
                break;
            case 'Aborted':
                $statusId = $this->settings['statusAborted']['value'];
                break;
            case 'Closed':
                $statusId = $this->settings['statusClosed']['value'];
                break;
        }

        return $statusId;
    }


}