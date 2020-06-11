<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 24/03/20
 * Time: 11:19
 */

namespace IMN\Controllers;


use IMN\Repositories\OrderRepository;
use IMN\Repositories\SettingsRepository;
use IMN\Services\HarvestService;
use Plenty\Modules\Item\Variation\Contracts\VariationRepositoryContract;
use Plenty\Modules\Item\Variation\Contracts\VariationSearchRepositoryContract;
use Plenty\Modules\Item\VariationSku\Contracts\VariationSkuRepositoryContract;
use Plenty\Modules\Order\Referrer\Contracts\OrderReferrerRepositoryContract;
use Plenty\Modules\Order\Shipping\ServiceProvider\Contracts\ShippingServiceProviderRepositoryContract;
use Plenty\Plugin\Controller;

class PlentyController extends Controller
{

    private $variationRepository;

    private $variationSearchRepository;

    private $harvestService;

    private $orderRepository;

    public function __construct(
        VariationSearchRepositoryContract $variationSearchRepository,
        VariationRepositoryContract $variationRepository,
        HarvestService $harvestService,
        OrderRepository $orderRepository
    )
    {
        $this->orderRepository = $orderRepository;
        $this->harvestService = $harvestService;
        $this->variationRepository = $variationRepository;
        $this->variationSearchRepository = $variationSearchRepository;
    }


    public function getShippingServiceProviders(ShippingServiceProviderRepositoryContract $shippingServiceProviderRepository) {
        return \json_encode($shippingServiceProviderRepository->all());
    }


    public function getImnOrderId($id) {
        $res = $this->orderRepository->getOrderByPlentyId($id);
        return \json_encode($res);
    }

    public function harvest() {
        try {
            $this->harvestService->synchronizeOrders();
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return json_encode(array("1"));
    }


    public function deleteOrderReferrer($id, OrderReferrerRepositoryContract $orderReferrerRepository) {
        $orderReferrerRepository->delete($id);
        return json_encode(array('success' => 1));
    }

    public function createIMNReferrer(OrderReferrerRepositoryContract $orderReferrerRepository, SettingsRepository $settingsRepository) {
        $orderReferrer = $orderReferrerRepository->create([
            'editable'    => false,
            'backendName' => 'IMN',
            'name'        => 'IMN - marketplace Name',
            'origin'      => 'IMN',
            'isFilterable' => true
        ]);

        $retries = 0;

        do
        {
            $settings = $settingsRepository->updateSettings('orderRefererId', array('value' => $orderReferrer->id));
            if($settings === false)
            {
                sleep(5);
            }
        }
        while($settings === false && ++$retries < 3);

        return $orderReferrer;
    }


    /**
     * {
    "error": {
    "message": "Invalid filter given."
    },
    "validation_errors": {
    "invalid filter": [
    "The following filter do not exist: number"
    ],
    "available filter": [
    [
    "itemId",
    "itemName",
    "id",
    "isMain",
    "isActive",
    "categoryId",
    "barcode",
    "numberFuzzy",
    "plentyId",
    "referrerId",
    "numberExact",
    "isBundle",
    "itemTagId",
    "supplierId",
    "supplierNumber",
    "createdBetween",
    "updatedBetween",
    "relatedUpdatedBetween",
    "manufacturerId",
    "flagOne",
    "flagTwo",
    "sku",
    "storeSpecial",
    "itemDescription",
    "stockWarehouseId"
    ]
    ]
    }
    }
     * @param $sku
     * @return string
     * @throws \Exception
     */
    public function findSku($sku) : string {
        $result = array();
        try {
            $this->variationSearchRepository->setFilters(array(
                'externalId' => $sku
            ));
            $this->variationSearchRepository->setSearchParams(array(
                'with' => array(
                    'item' => null,
                    'itemTexts' => null,
                    'variationSalesPrices' => null,
                    'stock'
                )
            ));
            $result = $this->variationSearchRepository->search();
        } catch(\Exception $ex) {
            throw $ex;
        }

        return json_encode($result);
    }


    public function findId($id) : string {
        try {
            $this->variationSearchRepository->setFilters(array(
                'id' => $id
            ));
            $this->variationSearchRepository->setSearchParams(array(
                'with' => array(
                    'item' => null,
                    'itemTexts' => null,
                    'variationSalesPrices' => null,
                    'stock' => null
                )
            ));
            $result = $this->variationSearchRepository->search();
        } catch(\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }

        //$variation = $this->variationRepository->findById($id);
        return json_encode($result);
    }

}