<?php
/**
 * OrderService.php
 *
 * @author Luis Ferrer <luis@bootdevelop.com>
 */

namespace IMN\Helper;

use IMN\Helper\LogHelper;
use Plenty\Modules\Item\Variation\Contracts\VariationSearchRepositoryContract;
use Plenty\Modules\Order\Models\OrderItemType;

class ProductHelper
{

    private $settings;

    private $vatField = 0;


    public function setSettings($settings) {
        $this->settings  = $settings;
    }


    public function getProductsFromImnOrder(
        $order,
        $productMap

    ) {
        $info = $order['info'];
        $pricingInfo = $info['pricingInfo'];
        $currency = $info['pricingInfo']['currencyCode'];
        $orderItems = $info['orderItems'];
        $orderProducts = array();
        foreach($orderItems as $orderItem) {
            $productExists = false;
            foreach($productMap as $map) {
                $product = $this->findMappedProduct($map, $orderItem, $currency);
                if($product) {
                    $productExists = true;
                    $orderProducts[] = $product;
                    break;
                }
            }

            if(!$productExists) {
                throw new \Exception("Unable to find product: ".$orderItem['merchantOfferSku']);
            }
        }

        if($order['info']['shippingInfo']['price'] > 0) {
            $orderProducts[] =  [
                'typeId'          => 6,
                'itemVariationId' => 0,
                'quantity'        => 1,
                'orderItemName'   => 'Shipping Costs - '.$order['info']['shippingInfo']['method'],
                'vatField'        => $this->vatField,
                'amounts'         => [
                    [
                        'priceOriginalGross' => $order['info']['shippingInfo']['price'],
                        'currency'           => $currency,
                    ],
                ],
            ];
        }

        if($pricingInfo['additionalFee'] > 0) {
            $orderProducts[] =  [
                'typeId'          => OrderItemType::TYPE_UNASSIGEND_VARIATION,
                'itemVariationId' => 0,
                'quantity'        => 1,
                'orderItemName'   => 'Marketplace Fee',
                'vatField'        => $this->vatField,
                'amounts'         => [
                    [
                        'priceOriginalGross' => round($pricingInfo['additionalFee'], 2),
                        'currency'           => $currency,
                    ],
                ],
            ];
        }



        return $orderProducts;
    }


    private function findMappedProduct($map, $orderItem, $currency) {
        $product = array();
        switch($map) {
            case 'variant_id':
                $product = $this->getProductByVariantId($orderItem['merchantOfferSku']);
                break;
            case 'sku':
                $product = $this->getProductBySku($orderItem['merchantOfferSku']);
                break;
        }

        if(empty($product)) {
            return false;
        }

        $qty = 0;
        foreach($product['stock'] as $stockItem) {
            $qty += $stockItem['physicalStock'];
            //$qty += $stockItem['netStock'];
        }
        $this->vatField = $product['vatId'];
        if($this->settings['importOrderIfNoStock']['value'] == 0) {
            if($orderItem['quantity'] > $qty) {
                throw new \Exception("Product ".$product['id']." doesn't have enough stock to import this order");
            }
        }
        return array(
            'typeId' => 1,
            'referrerId' => $this->settings['orderRefererId']['value'],
            'itemVariationId' => $product['id'],
            'quantity' => $orderItem['quantity'],
            'orderItemName' => $orderItem['title'],
            'vatField' => $product['vatId'],
            'amounts' => array(
                array(
                    'currency' => $currency,
                    'priceOriginalGross' => $orderItem['itemPrice']
                )

            )
        );

    }


    public function getProductByVariantId($variantId) {
        try {
            /**
             * @var $variationSearchRepository VariationSearchRepositoryContract
             */
            $variationSearchRepository = pluginApp(VariationSearchRepositoryContract::class);
            $variationSearchRepository->clearFilters();
            $variationSearchRepository->clearCriteria();
            $variationSearchRepository->setFilters(array(
                'id' => $variantId
            ));
            $variationSearchRepository->setSearchParams(array(
                'with' => array(
                    'item' => null,
                    'itemTexts' => null,
                    //'variationSalesPrices' => null,
                    'stock' => null
                )
            ));
            $result = $variationSearchRepository->search();
            if($result->getTotalCount() > 0) {
                return $result->toArray()['entries'][0];
            }
        } catch(\Exception $ex) {
            throw $ex;
        }

        return array();
    }

    public function getProductBySku($sku) {
        try {
            /**
             * @var $variationSearchRepository VariationSearchRepositoryContract
             */
            $variationSearchRepository = pluginApp(VariationSearchRepositoryContract::class);
            $variationSearchRepository->clearFilters();
            $variationSearchRepository->clearCriteria();
            $variationSearchRepository->setFilters(array(
                'numberExact' => $sku
            ));
            $variationSearchRepository->setSearchParams(array(
                'with' => array(
                    'item' => null,
                    'itemTexts' => null,
                    //'variationSalesPrices' => null,
                    'stock' => null
                )
            ));
            $result = $variationSearchRepository->search();
            if($result->getTotalCount() > 0) {
                return $result->toArray()['entries'][0];
            }
        } catch(\Exception $ex) {
            throw $ex;
        }


        return array();
    }


}