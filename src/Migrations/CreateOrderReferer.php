<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 5/05/20
 * Time: 13:41
 */

namespace IMN\Migrations;

use IMN\Helper\SettingsHelper;
use IMN\Repositories\SettingsRepository;
use Plenty\Modules\Order\Referrer\Contracts\OrderReferrerRepositoryContract;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;


class CreateOrderReferer
{
    private $settingsHelper;

    public function __construct(SettingsHelper $settingsHelper)
    {
        $this->settingsHelper = $settingsHelper;
    }


    public function run(OrderReferrerRepositoryContract $orderReferrerRepository) {
        $orderReferrer = $orderReferrerRepository->create([
            'editable'    => false,
            'backendName' => 'IMN',
            'name'        => 'IMN - marketplace Name',
            'origin'      => 'IMN',
            'isFilterable' => true
        ]);

        $this->settingsHelper->setProperty('orderRefererId',  $orderReferrer->id);
        $this->settingsHelper->save(true, true);
    }

}
