<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 23/03/20
 * Time: 16:15
 */

namespace IMN\Controllers;


use IMN\Repositories\OrderRepository;
use IMN\Services\Api\ImnClient;
use IMN\Services\Api\ImnOrderActions;
use IMN\Services\SettingsHelper;
use Plenty\Modules\Order\Status\Contracts\OrderStatusRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;

class IMNController extends Controller
{

    /**
     * @var ImnClient
     */
    private $client;

    /**
     * @var SettingsHelper
     */
    private $settingsService;

    private $settings;




    public function __construct(
        ImnClient $client,
        \IMN\Helper\SettingsHelper $settingsService

    )
    {
        $this->client = $client;
        $this->settingsService = $settingsService;
        $this->settings = $this->settingsService->getProperties();
        $this->client->init($this->settings['apiToken']['value'], $this->settings['merchantCode']['value']);
    }


    public function checkApiStatus(): string {
        return json_encode(array('status_ok' => $this->client->isCredentialOk()));
    }


    public function getStatusList(): string {
        return json_encode($this->client->getOrderStatus());
    }


    public function getMarketplaces(): string {
        $marketplaces = $this->client->getMarketplaces();
        $result = array();
        if(array_key_exists('marketplaces', $marketplaces)) {

            foreach($marketplaces['marketplaces'] as $marketplace) {
                $result[] = [
                    'name' => $marketplace['info']['name'],
                    'code' => $marketplace['info']['code'],
                    'subscriptionStatus' => $marketplace['info']['subscriptionStatus'],
                    'credentialStatus' => $marketplace['info']['credentialStatus'],
                    'shippingSettingsStatus' => $marketplace['info']['shippingSettingsStatus'],
                ];
            }
        }

        return json_encode($result);
    }

    public function getImnOrders(Request $request, OrderRepository $orderRepository) {
        $filterKeys = array(
            'marketplaceCode',
            'marketplaceOrderId',
            'merchantCode',
            'plentyOrderId',
            'imnStatus',
            'marketplaceStatus'
        );
        $data = $request->all();
        $filters = array();
        foreach($filterKeys as $key) {
            if(array_key_exists($key, $data)) {
                $filters[$key] = $data[$key];
            }
        }
        $page = $request->get('page',1);
        $itemsPerPage = $request->get('itemsPerPage',25);
        $sortBy = $request->get("sortBy", 'id');
        $sortOrder = $request->get("sortOrder", 'desc');

        return $orderRepository->getOrders($page, $itemsPerPage, $filters, $sortBy, $sortOrder);
    }

    public function getOrderActionSchema($schema, ImnOrderActions $imnOrderActions) {
        return json_encode($imnOrderActions->getParameters($schema));
    }

    public function changeOrder(Request $request, ImnOrderActions $imnOrderActions) {
        $postData = $request->all();
        $keys = array(
          'schema',
          'merchantCode',
          'marketplaceOrderId',
          'marketplaceCode',
          'plentyOrderId',
          'etag',
          'request'
        );

        foreach($keys as $key) {
            if(!array_key_exists($key, $postData)) {
                throw new \Exception($key." is missing");
            }
        }

        $request = $postData['request'];
        $parameters = array();
        foreach($request as $param) {
            $parameters[$param['name']] = $param['value'];
        }

        return array('success' => $imnOrderActions->changeOrder($postData['plentyOrderId'], $postData['schema'], $parameters));



    }

}