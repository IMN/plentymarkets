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
 * @property string $marketplaceStatus
 * @property string $imnStatus
 * @property float $marketplaceFee
 * @property string $lastModificationDate
 * @property string $lastMarketplaceModificationDate
 * @property string $purchaseDate
 * @property string $etag
 * @property array $transitionLinks
 * @property string $imnOrderLink
 * @property string $currency
 * @property string $channel
 * @property float $totalPaid
 * @property int $plentyOrderId
 * ----- NOT IMPLENETED Relation(model="Plenty\Modules\Order\Models\Order", name="imnorder_plenty_order_id_fk", attribute="id", column="plentyOrderId", onUpdate="Cascade", onDelete="Cascade")
 */
class Order extends Model
{

    public $id = 0;

    public $marketplaceCode = '';
    public $marketplaceOrderId = '';
    public $merchantCode = '';


    public $plentyOrderId  = 0;

    public $marketplaceStatus = '';
    public $imnStatus = '';
    public $marketplaceFee = '';
    public $lastModificationDate = '';
    public $lastMarketplaceModificationDate = '';
    public $purchaseDate = '';
    public $etag = '';
    public $transitionLinks = '';
    public $imnOrderLink = '';
    public $currency = '';
    public $totalPaid = '';
    public $channel = '';
    public $updatedAt = 0;

    public function getTableName()
    : string
    {
        return 'IMN::order';
    }
}