<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 12/03/20
 * Time: 13:17
 */
namespace IMN\Contracts;


use IMN\Models\Settings;

interface SettingsRepositoryContract
{


    /**
     *
     * @param array $data
     * @return Settings
     */
    public function addSettings(array $data): Settings;


    /**
     * List all Settings
     *
     * @return Settings[]
     */
    public function listSettings(): array;

    public function listMap(): array;


    /**
     *
     * @param int $id
     * @return Settings
     */
    public function updateSettings($name, array $data): Settings;

    /**
     *
     * @param int $id
     * @return Settings
     */
    public function deleteSettings($name): Settings;
}