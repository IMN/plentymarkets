<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 8/08/18
 * Time: 11:09
 */

namespace IMN\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;

class Settings extends Model
{

    public $id = 0;
    public $name = '';
    public $value = '';
    public $updatedAt = 0;

    public function getTableName()
    : string
    {
        return 'IMN::settings';
    }
}