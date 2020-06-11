<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 11/05/20
 * Time: 18:27
 */

namespace IMN\Services\Api;


use IMN\Models\Order;
use IMN\Repositories\OrderRepository;
use IMN\Helper\SettingsHelper;
use IMN\Services\HarvestService;


class ImnOrderActions
{

    public $errors = [];

    private $imnClient;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    private $harvestService;

    public function __construct(
        ImnClient $imnClient,
        SettingsHelper $settingsService,
        OrderRepository $orderRepository,
        HarvestService $harvestService
    )
    {
        $this->harvestService = $harvestService;
        $this->orderRepository = $orderRepository;
        $settings = $settingsService->getProperties();
        $this->imnClient = $imnClient;
        $this->imnClient->init($settings['apiToken']['value'], $settings['merchantCode']['value']);
        $apiStatusOk = $this->imnClient->isCredentialOk();
        if(!$apiStatusOk) {
            throw new \Exception("Api credentials are not correct");
        }
    }


    private static $schemaTypes = array(
        'acceptOrderRequest',
        'refuseOrderRequest',
        'refundOrderRequest',
        'shipOrderWithTrackingUrlRequest',
        'shipOrderRequest',
        'cancelOrderRequest',
    );

    public function getParameters($schema) {
        if(!in_array($schema, self::$schemaTypes)) {
            throw new \Exception("Schema provided does not exist");
        }
        $parameters = array();
        switch($schema) {
            case 'acceptOrderRequest':
                $parameters = $this->getParamAcceptOrderRequest();
                break;
            case 'refuseOrderRequest':
                $parameters = $this->getParamRefundOrderRequest();
                break;
            case 'refundOrderRequest':
                $parameters = $this->getParamRefundOrderRequest();
                break;
            case 'shipOrderWithTrackingUrlRequest':
                $parameters = $this->getParamShipOrderWithTrackingUrlRequest();
                break;
            case 'shipOrderRequest':
                $parameters = $this->getParamShipOrderRequest();
                break;
            case 'cancelOrderRequest':
                $parameters = $this->getParamCancelOrderRequest();
                break;


        }
        return array(
            'schema' => $schema,
            'parameters' => $parameters
        );
    }


    public function changeOrder($plentyOrderId, $schema, $parameters = array()) {
        /**
         * @var $order Order
         */
        $order = $this->orderRepository->getOrderByPlentyId($plentyOrderId);
        if(!$order) {
            throw new \Exception("Plenty order id does not exist");
        }

        $this->harvestService->createOrderFomGoLink($order->imnOrderLink);
        $order = $this->orderRepository->getOrderByPlentyId($plentyOrderId);
        return $this->sendRequest(
            $order->marketplaceCode,
            $order->marketplaceOrderId,
            $schema,
            $parameters,
            $order->etag
        );

    }

    public function sendRequest(
        $marketplaceCode,
        $marketplaceOrderId,
        $schema,
        $parameters = array(),
        $etag = ""
    ) {
        $testMode = false;
        $response = false;
        switch($schema) {
            case 'acceptOrderRequest':
                $response = $this->imnClient->acceptOrder($marketplaceCode, $marketplaceOrderId, $etag, $testMode);
                break;
            case 'refuseOrderRequest':
                $response = $this->imnClient->refuseOrder($marketplaceCode, $marketplaceOrderId, $etag, $testMode);
                break;
            case 'refundOrderRequest':
                if(!$this->checkRequiredParams(array('refundReason'), $parameters)) {
                    return false;
                }
                $response = $this->imnClient->refundOrder($marketplaceCode, $marketplaceOrderId, $parameters['refundReason'], $etag, $testMode);
                break;
            case 'shipOrderWithTrackingUrlRequest':
                if(!$this->checkRequiredParams(array('carrierName', 'trackingNumber', 'trackingUrl'), $parameters)) {
                    return false;
                }
                $response = $this->imnClient->shipOrderWithTrackingUrl( $marketplaceCode, $marketplaceOrderId, $parameters['trackingNumber'], $parameters['carrierName'], $parameters['trackingUrl'], $etag, $testMode);
                break;
            case 'shipOrderRequest':
                if(!$this->checkRequiredParams(array('carrierCode', 'trackingNumber'), $parameters)) {
                    return false;
                }
                $response = $this->imnClient->shipOrder($marketplaceCode, $marketplaceOrderId,  $parameters['trackingNumber'], $parameters['carrierCode'], $etag, $testMode);
                break;
            case 'cancelOrderRequest':
                if(!$this->checkRequiredParams(array('cancellationReason'), $parameters)) {
                    return false;
                }
                $response = $this->imnClient->cancelOrder( $marketplaceCode, $marketplaceOrderId, $parameters['cancellationReason'], $etag, $testMode);
                break;
        }


        //$this->errors = $this->imnClient->errors;
        return $response;
    }



    private function checkRequiredParams($keys, $params) {
        foreach($keys as $key) {
            if(!isset($params[$key])) {
                return false;
            }
        }
        return true;
    }


    //Get Paramaters for schema Types

    private function getParamAcceptOrderRequest() {
        return array();
    }


    private function getParamRefuseOrderRequest() {
        return array();
    }

    private function getParamRefundOrderRequest() {
        return array(
            array(
                'label' => "Refund Reason",
                'name' => 'refundReason',
                'type' => 'select',
                'options' => array(
                    array(
                        'value' => 'BuyerWarrantyOrRetractation',
                        'label' => 'Buyer Warranty Or Retractation'
                    ),
                    array(
                        'value' => 'WrongPrice',
                        'label' => 'Wrong Price'
                    ),
                    array(
                        'value' => 'LateDelivery',
                        'label' => 'Late Delivery'
                    ),
                    array(
                        'value' => 'IncorrectDelivery',
                        'label' => 'Incorrect Delivery'
                    ),
                    array(
                        'value' => 'IncompleteDelivery',
                        'label' => 'Incomplete Delivery'
                    ),
                    array(
                        'value' => 'DamagedDelivery',
                        'label' => 'Damaged Delivery'
                    ),
                    array(
                        'value' => 'BuyerFeedbackTimeout',
                        'label' => 'Buyer Feedback Timeout'
                    ),
                    array(
                        'value' => 'BuyerAgreement',
                        'label' => 'Buyer Agreement'
                    ),
                    array(
                        'value' => 'BuyerCancelled',
                        'label' => 'Buyer Cancelled'
                    ),
                    array(
                        'value' => 'NoInventory',
                        'label' => 'No Inventory'
                    ),
                    array(
                        'value' => 'ShippingAddressUndeliverable',
                        'label' => 'Shipping Address Undeliverable'
                    ),
                    array(
                        'value' => 'Other',
                        'label' => 'Other'
                    )
                )
            )
        );
    }

    private function getParamShipOrderWithTrackingUrlRequest() {
        return array(
            array(
                'label' => "Carrier Name",
                'name' => 'carrierName',
                'type' => 'text'
            ),
            array(
                'label' => "Tracking Number",
                'name' => 'trackingNumber',
                'type' => 'text'
            ),
            array(
                'label' => "Tracking Url",
                'name' => 'trackingUrl',
                'type' => 'text'
            )
        );
    }

    private function getParamShipOrderRequest() {
        return array(
            array(
                'label' => "Tracking Number",
                'name' => 'trackingNumber',
                'type' => 'text'
            ),
            array(
                'label' => "Carrier Code",
                'name' => 'carrierCode',
                'type' => 'select',
                'options' => array(
                    array(
                        'value' => 'DeutschePost',
                        'label' => 'Deutsche Post'
                    ),
                    array(
                        'value' => 'DHL',
                        'label' => 'DHL'
                    ),
                    array(
                        'value' => 'GLS',
                        'label' => 'GLS'
                    ),
                    array(
                        'value' => 'TNT',
                        'label' => 'TNT'
                    ),
                    array(
                        'value' => 'UPS',
                        'label' => 'UPS'
                    ),
                    array(
                        'value' => 'DPD',
                        'label' => 'DPD'
                    ),
                    array(
                        'value' => 'FEDEX',
                        'label' => 'FEDEX'
                    ),
                    array(
                        'value' => '4PX',
                        'label' => '4PX'
                    ),
                    array(
                        'value' => 'Bpost',
                        'label' => 'Bpost'
                    ),
                    array(
                        'value' => 'ChinaEMSePacket',
                        'label' => 'China EM Se Packet'
                    ),
                    array(
                        'value' => 'ChinaPost',
                        'label' => 'China Post'
                    ),
                    array(
                        'value' => 'Chronopost',
                        'label' => 'Chronopost'
                    ),
                    array(
                        'value' => 'CNEExpress',
                        'label' => 'CNE Express'
                    ),
                    array(
                        'value' => 'ColisPrive',
                        'label' => 'Colis Prive'
                    ),
                    array(
                        'value' => 'Colissimo',
                        'label' => 'Colissimo'
                    ),
                    array(
                        'value' => 'GEODIS',
                        'label' => 'GEODIS'
                    ),
                    array(
                        'value' => 'LaPosteCourrier',
                        'label' => 'La Poste Courrier'
                    ),
                    array(
                        'value' => 'MalaysiaPost',
                        'label' => 'Malaysia Post'
                    ),
                    array(
                        'value' => 'MondialRelay',
                        'label' => 'Mondial Relay'
                    ),
                    array(
                        'value' => 'PostNL',
                        'label' => 'Post NL'
                    ),
                    array(
                        'value' => 'RelaisColis',
                        'label' => 'Relais Colis'
                    ),
                    array(
                        'value' => 'RoyalMail',
                        'label' => 'Royal Mail'
                    ),
                    array(
                        'value' => 'SFExpress',
                        'label' => 'SF Express'
                    ),
                    array(
                        'value' => 'SFCService',
                        'label' => 'SFC Service'
                    ),
                    array(
                        'value' => 'SingaporePost',
                        'label' => 'Singapore Post'
                    ),
                    array(
                        'value' => 'USPS',
                        'label' => 'USPS'
                    ),
                    array(
                        'value' => 'Yanwen',
                        'label' => 'Yanwen'
                    ),
                    array(
                        'value' => 'AMATI',
                        'label' => 'AMATI'
                    ),
                    array(
                        'value' => 'BRTID',
                        'label' => 'BRTID'
                    ),
                    array(
                        'value' => 'BRTRIFMIT',
                        'label' => 'BRTRIFMIT'
                    ),
                    array(
                        'value' => 'BRTSPED',
                        'label' => 'BRTSPED'
                    ),
                    array(
                        'value' => 'CORREOS',
                        'label' => 'CORREOS'
                    ),
                    array(
                        'value' => 'ENERGO',
                        'label' => 'ENERGO'
                    ),
                    array(
                        'value' => 'FERCAM',
                        'label' => 'FERCAM'
                    ),
                    array(
                        'value' => 'NEXIVE',
                        'label' => 'NEXIVE'
                    ),
                    array(
                        'value' => 'ItalianPost',
                        'label' => 'Italian Post'
                    ),
                    array(
                        'value' => 'SDA',
                        'label' => 'SDA'
                    ),
                    array(
                        'value' => 'SGTFlyer',
                        'label' => 'SGT Flyer'
                    ),
                    array(
                        'value' => 'TECNOTRANS',
                        'label' => 'TECNOTRANS'
                    ),
                    array(
                        'value' => 'Bursped',
                        'label' => 'Bursped'
                    ),
                    array(
                        'value' => 'Cargoline',
                        'label' => 'Cargoline'
                    ),
                    array(
                        'value' => 'Computeruniverse',
                        'label' => 'Computeruniverse'
                    ),
                    array(
                        'value' => 'Dachser',
                        'label' => 'Dachser'
                    ),
                    array(
                        'value' => 'DHLFreight',
                        'label' => 'DHL Freight'
                    ),
                    array(
                        'value' => 'dtl',
                        'label' => 'dtl'
                    ),
                    array(
                        'value' => 'Emons',
                        'label' => 'Emons'
                    ),
                    array(
                        'value' => 'GEL',
                        'label' => 'GEL'
                    ),
                    array(
                        'value' => 'Hellmann',
                        'label' => 'Hellmann'
                    ),
                    array(
                        'value' => 'Hermes',
                        'label' => 'Hermes'
                    ),
                    array(
                        'value' => 'Hermes2MH',
                        'label' => 'Hermes 2MH'
                    ),
                    array(
                        'value' => 'IDSLogistik',
                        'label' => 'IDS Logistik'
                    ),
                    array(
                        'value' => 'Iloxx',
                        'label' => 'Iloxx'
                    ),
                    array(
                        'value' => 'KuehneNagel',
                        'label' => 'Kuehne Nagel'
                    ),
                    array(
                        'value' => 'Marktanlieferung',
                        'label' => 'Marktanlieferung'
                    ),
                    array(
                        'value' => 'Rhenus',
                        'label' => 'Rhenus'
                    ),
                    array(
                        'value' => 'Schenker',
                        'label' => 'Schenker'
                    ),
                    array(
                        'value' => 'SpeditionGuettler',
                        'label' => 'Spedition Guettler'
                    )
                )
            )
        );
    }

    private function getParamCancelOrderRequest() {
        return array(
            array(
                'label' => "Cancellation Reason",
                'name' => 'cancellationReason',
                'type' => 'select',
                'options' => array(
                    array(
                        'value' => 'WrongPrice',
                        'label' => 'Wrong Price'
                    ),
                    array(
                        'value' => 'DelayedInventory',
                        'label' => 'Delayed Inventory'
                    ),
                    array(
                        'value' => 'WrongProductInfo',
                        'label' => 'Wrong Product Info'
                    ),
                    array(
                        'value' => 'BuyerFeedbackTimeout',
                        'label' => 'Buyer Feedback Timeout'
                    ),
                    array(
                        'value' => 'BuyerAgreement',
                        'label' => 'Buyer Agreement'
                    ),
                    array(
                        'value' => 'BuyerCancelled',
                        'label' => 'Buyer Cancelled'
                    ),
                    array(
                        'value' => 'NoInventory',
                        'label' => 'No Inventory'
                    ),
                    array(
                        'value' => 'ShippingAddressUndeliverable',
                        'label' => 'Shipping Address Undeliverable'
                    ),
                    array(
                        'value' => 'Other',
                        'label' => 'Other'
                    ),
                )
            )
        );


    }



}
