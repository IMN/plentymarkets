<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 5/05/20
 * Time: 13:41
 */

namespace IMN\Migrations;

use IMN\Repositories\SettingsRepository;
use Plenty\Modules\Order\Referrer\Contracts\OrderReferrerRepositoryContract;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;


class CreateOrderReferer
{
    private $settingsRepository;

    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }


    public function run(OrderReferrerRepositoryContract $orderReferrerRepository) {
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
            $settings = $this->settingsRepository->updateSettings('orderRefererId', array('value' => $orderReferrer->id));
            if($settings === false)
            {
                sleep(5);
            }
        }
        while($settings === false && ++$retries < 3);
    }

}