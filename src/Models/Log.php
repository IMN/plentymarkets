<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 8/08/18
 * Time: 11:09
 */

namespace IMN\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;

/**
 * @property string $marketplaceCode
 * @property string $marketplaceOrderId
 * @property string $merchantCode
 * @property string $type
 * @property string $message
 * @property int $dateAdd
 */
class Log extends Model
{

    public $id = 0;

    public $marketplaceCode = '';
    public $marketplaceOrderId = '';
    public $merchantCode = '';


    public $type = '';
    public $message = '';
    public $dateAdd = 0;


    public function getTableName()
    : string
    {
        return 'IMN::log';
    }
}