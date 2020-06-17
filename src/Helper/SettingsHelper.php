<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 16/03/20
 * Time: 17:57
 */

namespace IMN\Helper;


use IMN\Repositories\SettingsRepository;

class SettingsHelper
{

    private $settingsRepository;


    private $properties = array(
        'apiToken' => array(
            'type' => 'string',
            'value' => ''
        ),
        'merchantCode' => array(
            'type' => 'string',
            'value' => '',
            'validate' => [
                'regex' => '/^([A-Z0-9]{5})$/',
                'error_msg' => 'Merchant code has to contain 5 characters and has to be alphanumeric'
            ]
        ),
        'lastSyncTime' => array(
            'type' => 'string',
            'value' => '',
            'validate' => [
                'regex' => '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])(\s{1}[0-9]{2}:[0-9]{2}(:[0-9]{2})?)?$/',
                'error_msg' => 'Last Sync Time has to have the following format: YYYY-mm-dd HH:ii:ss'
            ]
        ),
        'productMap' => array(
            'type' => 'json',
            'value' => array(
                'sku'
            )
        ),
        'enableCron' => array(
            'type' => 'int',
            'value' => '0'
        ),
        'statusNew' => array(
            'type' => 'string',
            'value' => '19'
        ),
        'statusInProgress' => array(
            'type' => 'string',
            'value' => '4'
        ),
        'statusShipped' => array(
            'type' => 'string',
            'value' => '19.2'
        ),
        'statusClosed' => array(
            'type' => 'string',
            'value' => '19.3'
        ),
        'statusCancelled' => array(
            'type' => 'string',
            'value' => '8'
        ),
        'statusAborted' => array(
            'type' => 'string',
            'value' => '8'
        ),
        'apiStatus' => array(
            'type' => 'int',
            'value' => '0'
        ),
        'orderRefererId' => array(
            'type' => 'int',
            'value' => '0'
        ),
        'autoshipMap' => array(
            'type' => 'json',
            'value' => array()
        ),
        'importOrderIfNoStock' => array(
            'type' => 'int',
            'value' => '1'
        )
    );

    public function __construct(SettingsRepository $settingsRepository)
    {
        if(empty($this->properties['lastSyncTime']['value'])) {
            $this->properties['lastSyncTime']['value'] = gmdate('Y-m-d H:i:s');
        }

        $this->settingsRepository = $settingsRepository;
        $this->assignSettings();
    }


    public function getProperties() {
        return $this->properties;
    }

    public function assignSettings() {

        $settings = $this->settingsRepository->listSettings();
        foreach($settings as $setting) {
            if(!isset($this->properties[$setting->name])) {
                continue;
            }

            $key = $setting->name;
            $value = $setting->value;
            $type = $this->properties[$setting->name]['type'];
            if($type == 'json') {
                $value = \json_decode($value, true);
            }
            $this->properties[$setting->name]['value'] = $value;
        }

    }

    public function setProperty($name, $value) {
        if(!isset($this->properties[$name])) {
            return false;
        }
        $this->properties[$name]['value'] = $value;
        return true;
    }


    public function save($encodeJson = true, $removeExceptions = false) {
        foreach($this->properties as $key => $property) {
            $value = $property['value'];
            if($property['type'] == 'json' && $encodeJson) {
                $value = \json_encode($value);
            }
            if(array_key_exists('validate', $property) && array_key_exists('regex', $property['validate'])) {
                if(!preg_match($property['validate']['regex'], $value) && !$removeExceptions) {
                    if(array_key_exists('error_msg', $property['validate'])) {
                        throw new \Exception($property['validate']['error_msg'], 400);
                    }
                    $value = '';
                    if($property['type'] == 'json') {
                        $value = '[]';
                    }
                }
            }
            $this->settingsRepository->addSettings(array(
                'name' => $key,
                'value' => $value
            ));
        }
    }
}