<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 21/05/20
 * Time: 16:41
 */

namespace IMN\Crons;


use IMN\Helper\SettingsHelper;
use IMN\Services\HarvestService;
use Plenty\Modules\Cron\Contracts\CronHandler;

class HarvestCron extends CronHandler
{

    public function handle(
        HarvestService $harvestService,
        SettingsHelper $settingsHelper
    ) {
        $settings = $settingsHelper->getProperties();
        if($settings['enableCron']['value'] == 1) {
            $harvestService->synchronizeOrders();
        }
    }

}