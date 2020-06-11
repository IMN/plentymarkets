<?php
//https://github.com/plentymarkets/plugin-payment-payuponpickup
namespace IMN\Providers;

use IMN\Contracts\SettingsRepositoryContract;
use IMN\Crons\HarvestCron;
use IMN\EventProcedures\Autoship;
use IMN\Repositories\SettingsRepository;
use IMN\Services\SettingsHelper;
use Plenty\Modules\Cron\Services\CronContainer;
use Plenty\Modules\EventProcedures\Services\Entries\ProcedureEntry;
use Plenty\Modules\EventProcedures\Services\EventProceduresService;
use Plenty\Plugin\ServiceProvider;

class IMNServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->getApplication()->register(IMNRouteServiceProvider::class);
        $this->getApplication()->bind(SettingsRepositoryContract::class, SettingsRepository::class);
    }


    public function boot(
        CronContainer $cronContainer,
        \IMN\Helper\SettingsHelper $settingsService,
        EventProceduresService $eventProceduresService
    ) {

        $cronContainer->add(CronContainer::EVERY_TWENTY_MINUTES, HarvestCron::class);
        $eventProceduresService->registerProcedure(
            'imn',
            ProcedureEntry::PROCEDURE_GROUP_ORDER,
            ['de' => 'IMN Autoship', 'en' => 'IMN Autoship'],
            Autoship::class.'@run'
        //ProcedureEntry::PROCEDURE_GROUP_SHIPPING
        );

        $settingsService->save();
    }
}