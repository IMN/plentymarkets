<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 29/04/20
 * Time: 17:50
 */

namespace IMN\Repositories;


use IMN\Contracts\OrderRepositoryContract;
use IMN\Models\Order;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

class OrderRepository implements OrderRepositoryContract
{

    public function getOrders(
        int $page,
        int $itemsPerPage,
        array $filters,
        string $sortBy,
        string $sortOrder
    ) {

        $filterKeys = array(
            'marketplaceCode',
            'marketplaceOrderId',
            'merchantCode',
            'plentyOrderId',
            'imnStatus',
            'marketplaceStatus'
        );

        /**
         * @var $database Database
         */
        $database = pluginApp(DataBase::class);

        $query =  $database->query(Order::class);

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
        $totalCount = $database->query(Order::class)->getCountForPagination();
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
    public function getOrderByPlentyId($plentyOrderId)
    {
        $database = pluginApp(DataBase::class);
        $orderList = $database->query(Order::class)
            ->where('plentyOrderId', '=', $plentyOrderId)
            ->get();
        if(!$orderList) {
            return false;
        }
        return $orderList[0];
    }

    public function getOrder($marketplaceCode, $merchantCode, $marketplaceOrderId)
    {

        $database = pluginApp(DataBase::class);
        $orderList = $database->query(Order::class)
            ->where('marketplaceCode', '=', $marketplaceCode)
            ->where('merchantCode', '=', $merchantCode)
            ->where('marketplaceOrderId', '=', $marketplaceOrderId)
            ->get();
        if(!$orderList) {
            return false;
        }
        return $orderList[0];

    }


    public function createOrder($plentyOrderId, $imnOrder) {
        /**
         * @var $database DataBase
         */
        $database = pluginApp(DataBase::class);
        /**
         * @var $order Order
         */
        $order = pluginApp(Order::class);
        $order->plentyOrderId = $plentyOrderId;
        $this->populateOrder($order, $imnOrder);
        $database->save($order);
        return $order;
    }


    public function updateOrder(Order $order, $imnOrder) {
        /**
         * @var $database DataBase
         */
        $database = pluginApp(DataBase::class);
        $this->populateOrder($order, $imnOrder);
        $database->save($order);
        return $order;
    }

    private function populateOrder(Order &$order, $imnOrder) {
        $info = $imnOrder['info'];
        $identifier = $info['identifier'];
        $pricingInfo = $info['pricingInfo'];
        $generalInfo = $info['generalInfo'];
        $order->marketplaceOrderId = $identifier['marketplaceOrderId'];
        $order->marketplaceCode = $identifier['marketplaceCode'];
        $order->merchantCode = $identifier['merchantCode'];
        $order->totalPaid = $pricingInfo['totalPrice'];
        $order->currency = $pricingInfo['currencyCode'];
        $order->etag = $info['imnInfo']['etag'];
        $order->imnOrderLink = $imnOrder['links']['go'];
        $order->imnStatus = $generalInfo['imnOrderStatus'];
        $order->lastMarketplaceModificationDate = $generalInfo['marketplaceLastModificationUtcDate'];
        $order->lastModificationDate = $info['imnInfo']['imnLastModificationUtcDate'];
        $order->marketplaceFee = (float)$pricingInfo['additionalFee'];
        $order->marketplaceStatus = $generalInfo['marketplaceOrderStatus'];
        $order->purchaseDate = $generalInfo['purchaseUtcDate'];
        $order->channel = $generalInfo['marketplaceChannel'];
        $order->transitionLinks = \json_encode($imnOrder['transitionLinks']);
    }
}