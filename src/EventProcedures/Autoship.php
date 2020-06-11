<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 7/05/20
 * Time: 17:14
 */

namespace IMN\EventProcedures;

use IMN\Helper\SettingsHelper;
use IMN\Repositories\OrderRepository as IMNOrderRepository;
use IMN\Services\Api\ImnOrderActions;
use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;
use Plenty\Modules\Listing\ShippingProfile\Contracts\ShippingProfileRepositoryContract;
use Plenty\Modules\Listing\ShippingProfile\Models\ShippingProfile;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Shipping\Contracts\ParcelServicePresetRepositoryContract;
use Plenty\Modules\Order\Shipping\ParcelService\Models\ParcelService;
use Plenty\Modules\Order\Shipping\ParcelService\Models\ParcelServicePreset;
use Plenty\Modules\Order\Shipping\ServiceProvider\Contracts\ShippingServiceProviderRepositoryContract;

class Autoship
{

    private $orderRepository;

    private $parcelServiceRepository;

    private $settingsHelper;

    private $imnOrderRepository;

    private $imnOrderActions;

    public function __construct(
        OrderRepositoryContract $orderRepository,
        ParcelServicePresetRepositoryContract $parcelServicePresetRepository,
        SettingsHelper $settingsHelper,
        IMNOrderRepository $imnOrderRepository,
        ImnOrderActions $imnOrderActions
    )
    {
        $this->orderRepository = $orderRepository;
        $this->parcelServiceRepository = $parcelServicePresetRepository;
        $this->settingsHelper = $settingsHelper;
        $this->imnOrderRepository = $imnOrderRepository;
        $this->imnOrderActions = $imnOrderActions;
    }


    public function run(EventProceduresTriggered $event) {
        $order = $event->getOrder();
        $imnOrder = $this->imnOrderRepository->getOrderByPlentyId($order->id);
        if(!$imnOrder) {
            return;
        }
        $trackingCode = $this->getTrackingCode($order);
        if($trackingCode == null) {
            return;
        }

        $settings = $this->settingsHelper->getProperties();
        $autoshipMap = $settings['autoshipMap']['value'];
        if(!isset($autoshipMap[$imnOrder->marketplaceCode])) {
            return;
        }
        $carrierCode = $autoshipMap[$imnOrder->marketplaceCode];

        $this->imnOrderActions->changeOrder($order->id, 'shipOrderRequest', array(
            'trackingNumber' => $trackingCode,
            'carrierCode' => $carrierCode
        ));;

    }



    private function getTrackingCode(Order $order) {
        try {
            $packageNumbers = $this->orderRepository->getPackageNumbers($order->id);
            if(is_array($packageNumbers) && count($packageNumbers))
            {
                return $packageNumbers[0];
            }
        } catch(\Exception $ex) {

        }

        return null;
    }

//    private function getCarrierName(Order $order) {
//        try {
//
//            //ShippingServiceProviderRepositoryContract
//            $parcelServicePreset = $this->parcelServiceRepository->getPresetById($order->shippingProfileId);
//
//            /**
//             * @var $test ParcelServicePreset
//             */
//            $test = null;
//            $test->shi
//            if($parcelServicePreset instanceof ParcelServicePreset) {
//                return
//            }
//        } catch (\Exception $ex) {
//
//        }
//    }

}