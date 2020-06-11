<?php
/**
 * BeezUPRouteServiceProvider.php
 *
 * @author Luis Ferrer <luis@bootdevelop.com>
 */

namespace IMN\Providers;


use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\ApiRouter;
use Plenty\Plugin\Routing\Router;

class IMNRouteServiceProvider extends RouteServiceProvider
{

    /**
     * @param Router $router
     */
    public function map(ApiRouter $api, Router $router)
    {

        $api->version(['v1'], ['namespace' => 'IMN\Controllers', 'middleware' => ['oauth']], function ($apiRouter) {
            
            //Settings routes
            $apiRouter->get('imn/settings/save', 'SettingsController@save');
            $apiRouter->get('imn/settings/list', 'SettingsController@list');
            $apiRouter->get('imn/settings/list/map', 'SettingsController@listMap');
            $apiRouter->post('imn/settings/add', 'SettingsController@add');
            $apiRouter->put('imn/settings/updatelist', 'SettingsController@updateList');
            $apiRouter->put('imn/settings/update/{name}', 'SettingsController@update')->where('name',  '[a-zA-Z\-_]+');
            $apiRouter->delete('imn/settings/delete/{name}', 'SettingsController@delete')->where('name', '[a-zA-Z\-_]+');

            //IMN routes
            $apiRouter->get('imn/api/checkstatus', "IMNController@checkApiStatus");
            $apiRouter->get('imn/api/getstatuslist', "IMNController@getStatusList");
            $apiRouter->get('imn/api/orders', 'IMNController@getImnOrders');
            $apiRouter->get('imn/api/omactionschema/{schema}', 'IMNController@getOrderActionSchema')->where('schema', '[a-zA-Z]+');
            $apiRouter->put('imn/api/changeorder', 'IMNController@changeOrder');
            $apiRouter->get('imn/api/marketplaces', 'IMNController@getMarketplaces');




            //Plenty routes
            $apiRouter->get('imn/plenty/carrierlist', 'PlentyController@getShippingServiceProviders');
            $apiRouter->get('imn/plenty/findsku/{sku}', 'PlentyController@findSku')->where('sku', '[a-zA-Z0-9\-_]+');
            $apiRouter->get('imn/plenty/findid/{id}', 'PlentyController@findId')->where('id', '[0-9]+');

            $apiRouter->get('imn/plenty/harvest', 'PlentyController@harvest');
            $apiRouter->get('imn/plenty/imnorder/{id}', 'PlentyController@getImnOrderId')->where('id', '[0-9]+');

            $apiRouter->get('imn/plenty/delete/referrer/{id}', 'PlentyController@deleteOrderReferrer');
            $apiRouter->get('imn/plenty/create/referrer', 'PlentyController@createIMNReferrer');


            //Harvest routes
            $apiRouter->post('imn/harvest/one', 'HarvestController@harvestOrder');
            $apiRouter->post('imn/harvest/full', 'HarvestController@harvestOrders');

            //Log routes
            $apiRouter->get('imn/log/search', 'LogController@getLog');

        });

    }

}