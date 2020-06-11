<?php

namespace IMN\Repositories;

use IMN\Services\Api\ImnClient;
use Plenty\Exceptions\ValidationException;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use IMN\Validators\SettingsValidator;
use IMN\Models\Settings;
use IMN\Contracts\SettingsRepositoryContract;

class SettingsRepository implements SettingsRepositoryContract
{

    private $imnClient;

    public function __construct(ImnClient $imnClient)
    {
        $this->imnClient = $imnClient;
    }


    /**
     *
     * @param array $data
     * @return Settings
     */
    public function addSettings(array $data): Settings
    {

        try {
            SettingsValidator::validateOrFail($data);
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage()." - ".\json_encode($data), 400);
        }
        $database = pluginApp(DataBase::class);
        $setting = $this->getByName($data['name']);
        if($setting) {
            $setting->value = $data['value'];
            $setting->updatedAt = time();
            $database->save($setting);
            return $setting;
        }

        $setting = pluginApp(Settings::class);
        $setting->name = $data['name'];
        $setting->value = $data['value'];
        $setting->updatedAt = time();
        $database->save($setting);
        return $setting;
    }

    /**
     * List all Settings
     *
     * @return Settings[]
     */
    public function listSettings(): array
    {
        /**
         * @var $database DataBase
         */
        $database = pluginApp(DataBase::class);
        return $database->query(Settings::class)->get();
    }

    public function listMap(): array
    {
        $result = array();
        $settings = $this->listSettings();
        foreach($settings as $setting) {
            $result[$setting->name] = $setting;
        }

        $this->imnClient->init($result['apiToken']->value, $result['merchantCode']->value);
        $result['apiStatus']->value = $this->imnClient->isCredentialOk();
        return $result;
    }


    public function getByName($name)  {
        $database = pluginApp(DataBase::class);
        $settingList = $database->query(Settings::class)
            ->where('name', '=', $name)
            ->get();
        if(!$settingList) {
            return false;
        }
        return $settingList[0];
    }

    /**
     *
     * @param int $id
     * @return Settings
     */
    public function updateSettings($name, array $data): Settings
    {
        $database = pluginApp(DataBase::class);
        if(!isset($data['value'])) {
           throw new \Exception("Error value key is missing", 400);
        }
        /**
         * @var DataBase $database
         */
        $setting = $this->getByName($name);
        if(!$setting) {
            throw new \Exception("This setting does not exist", 400);
        }


        $setting->value = $data['value'];
        $setting->updatedAt = time();
        $database->save($setting);
        return $setting;

    }

    /**
     *
     * @param int $id
     * @return Settings
     */
    public function deleteSettings($name): Settings
    {
        $database = pluginApp(DataBase::class);
        $setting = $this->getByName($name);
        if(!$setting) {
            throw new \Exception("This setting does not exist", 400);
        }
        $database->delete($setting);
        return $setting;
    }
}