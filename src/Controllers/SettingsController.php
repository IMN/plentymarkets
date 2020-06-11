<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 8/08/18
 * Time: 11:06
 */

namespace IMN\Controllers;


use IMN\Contracts\SettingsRepositoryContract;
use IMN\Models\Settings;
use IMN\Repositories\SettingsRepository;
use IMN\Helper\SettingsHelper;
use IMN\Services\Api\ImnClient;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Controller;
use Exception;

class SettingsController extends Controller
{

    public function save(SettingsHelper $settingsService): string {
        try {
            $settingsService->save();
        } catch(Exception $ex) {
            throw new \Exception($ex->getMessage(), 400);
        }

        return json_encode(array("success" => 1));
    }




    public function list(
        SettingsRepositoryContract $settingsRepo
    ) : array {
        return $settingsRepo->listSettings();
    }

    public function listMap(
        SettingsRepositoryContract $settingsRepo
    ) : array {
        return $settingsRepo->listMap();

    }

    public function add(
        Request $request,
        SettingsRepositoryContract $settingsRepo
    ) : string {
        $settings = $settingsRepo->addSettings($request->all());
        return json_encode($settings);
    }


//    public function updateList(
//        Request $request,
//        SettingsRepository $settingsRepository
//    ) {
//        $requestData = $request->all();
//        $response = [];
//        foreach($requestData['settings'] as $setting) {
//            $response[] = $settingsRepository->updateSettings($setting['name'], $setting);
//        }
//        return json_encode($response);
//    }

    public function updateList(
        Request $request,
        SettingsHelper $settingsHelper,
        ImnClient $imnClient
    ) {
        $settings = $settingsHelper->getProperties();

        $requestData = $request->all();

        $apiToken = $settings['apiToken']['value'];
        $merchantCode = $settings['merchantCode']['value'];
        if(array_key_exists('apiToken', $requestData) && array_key_exists('merchantCode', $requestData)) {
            $apiToken = $requestData['apiToken'];
            $merchantCode = $requestData['merchantCode'];
        }


        $imnClient->init($apiToken, $merchantCode);

        $result = [];
        foreach($requestData['settings'] as $setting) {
            if($setting['name'] == 'autoshipMap') {
                $autoshipMap = $settings['autoshipMap']['value'];
                if(empty($autoshipMap)) {
                    $autoshipMap = array();
                    $marketplaces = $imnClient->getMarketplaces();
                    if(array_key_exists('marketplaces', $marketplaces)) {
                        foreach($marketplaces['marketplaces'] as $marketplace) {
                            $autoshipMap[$marketplace['info']['code']] = "DeutschePost";
                        }
                    }
                    $settingsHelper->setProperty($setting['name'], \json_encode($autoshipMap));
                    $result[] = array(
                        'name' => 'autoshipMap',
                        'value' => \json_encode($autoshipMap)
                    );
                    continue;
                }
            }

                $settingsHelper->setProperty($setting['name'], $setting['value']);
                $result[] = $setting;
        }
        $settingsHelper->save(false);
        return \json_encode($result);
    }



    public function update(
        string $name,
        Request $request,
        SettingsRepositoryContract $settingsRepo
    ) : string {
        $settings = $settingsRepo->updateSettings($name, $request->all());
        return json_encode($settings);
    }

    public function delete(
        string $name,
        SettingsRepositoryContract $settingsRepo
    ) : string {
        $settings = $settingsRepo->deleteSettings($name);
        return json_encode($settings);
    }

}