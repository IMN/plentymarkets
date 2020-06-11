<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 23/03/20
 * Time: 16:11
 */

namespace IMN\Services\Api;

class ImnClient
{

    /**
     * @var IMNHttp
     */
    private $http;
    private $merchantCode;

    public function __construct(HttpClient $http) {
        $this->http = $http;
    }



    public function init($apiKey, $merchantCode)  {
        $this->http->setApiKey($apiKey);
        $this->merchantCode = $merchantCode;
    }


    public function isCredentialOk() {
        $accountIndex = $this->getMerchantAccountIndex();
        return isset($accountIndex['merchantCode']);
    }

    public function getOrderStatus() {
        return array(
            array(
                'name' => 'New',
                'value' => 'New'
            ),
            array(
                'name' => 'Pending',
                'value' => 'Pending'
            ),
            array(
                'name' => 'InProgress',
                'value' => 'In Progress'
            ),
            array(
                'name' => 'Received',
                'value' => 'Received'
            ),
            array(
                'name' => 'Shipped',
                'value' => 'Shipped'
            ),
            array(
                'name' => 'Cancelled',
                'value' => 'Cancelled'
            ),
            array(
                'name' => 'Closed',
                'value' => 'Closed'
            ),
            array(
                'name' => 'AvailableOnStore',
                'value' => 'Available on store'
            )
        );
    }

    public function getMerchantAccountIndex() {
        return $this->http->get('/merchant/account/v1/');
    }


    public function getMarketplaces() {
        return $this->http->get('/merchant/marketplaces/v1/'.$this->merchantCode);
    }

    public function getOrderList(
        $pageSize,
        $pageNumber,
        $beginDate = null,
        $endDate = null,
        $encoding = "utf-8"

    ) {
        $this->http->addHeader('Accept-Encoding', 'utf-8');
        $body = array(
            'pageNumber' => $pageNumber,
            'pageSize' => $pageSize,
            'dateSearchType' => 'IMNModification',
            'beginPeriodUtcDate' => date('Y-m-d\TH:i:s\Z', strtotime($beginDate)),
            'endPeriodUtcDate' => date('Y-m-d\TH:i:s\Z', strtotime($endDate))
        );
        $response = $this->http->post(
            '/merchant/orders/v1/'.$this->merchantCode.'/list/full',
            $body
        );

        if($this->http->info['http_code'] != '200') {
            throw new \Exception($this->http->info['http_code']." Some error ocurred");
        }


        return $response;

    }

    public function getOrder(
        $marketplaceCode,
        $marketplaceOrderId,
        $ifNoneMatch = ""

    ) {
        $this->http->addHeader('If-None-Match', $ifNoneMatch);
        $response = $this->http->get(
            "/merchant/orders/v1/$this->merchantCode/$marketplaceCode/$marketplaceOrderId"
        );

        $this->http->clearHeader('If-None-Match');

        if($this->http->info['http_code'] != '200') {
            throw new \Exception($this->http->info['http_code']." Some error ocurred");
        }

        return $response;
    }


    public function setMerchantOrderInfo(
        $marketplaceCode,
        $marketplaceOrderId,
        $platformOrderId,
        $platformSoftwareName,
        $platformSoftwareVersion
    ) {

        $body = array(
            'merchantOrderId' => $platformOrderId,
            'merchantECommerceSoftwareName' => $platformSoftwareName,
            'merchantECommerceSoftwareVersion' => $platformSoftwareVersion
        );
        $response = $this->http->post(
            "/merchant/orders/v1/$this->merchantCode/$marketplaceCode/$marketplaceOrderId/setMerchantOrderInfo",
            $body
        );

        switch($this->http->info['http_code']) {
            case '400':
                $errors[] = "400 Could not update Order merchant information.: ".$this->http->rawResponse;
                break;
            case '404':
                $errors[] = "404 The requested order is not found.";
                break;
        }

        if(empty($errors) && $this->http->info['http_code'] != '202') {
            $errors[] = $this->http->info['http_code']." Some error ocurred";
        }

        if(!empty($errors)) {
            throw new \Exception(implode(", ", $errors));
        }

        return $response;
    }


    private function changeOrder(
        $action,
        $marketplaceCode,
        $marketplaceOrderId,
        $body = array(),
        $ifMatch = "",
        $testMode = false
    ) {
        $userName = 'plentymarkets';
        $this->http->addHeader('If-Match', $ifMatch);
        $strTestMode = ($testMode) ? 'true' : 'false';
        $response = $this->http->post(
            "/merchant/orders/v1/$this->merchantCode/$marketplaceCode/$marketplaceOrderId/$action?userName=$userName&testMode=$strTestMode",
            $body
        );
        $errors = [];
        switch($this->http->info['http_code']) {
            case '400':
                $errors[] = "400 Invalid order ship request, could not be send to the marketplace: ".$this->http->rawResponse;
                break;
            case '404':
                $errors[] = "404 The requested order is not found.";
                break;
            case '409':
                $errors[] = "409 Already processing a change request for this Order.";
                break;
            case "412":
                $errors[] = "412 The ETag sent in the http header If-Match did not match with the current version.";
                break;
        }

        if(empty($errors) && $this->http->info['http_code'] != '202') {
            $errors[] = $this->http->info['http_code']." Some error ocurred";
        }

        if(!empty($errors)) {
            throw new \Exception(implode(", ", $errors));
        }

        return true;
    }

    public function acceptOrder(
        $marketplaceCode,
        $marketplaceOrderId,
        $ifMatch = "",
        $testMode = false
    ) {
        return $this->changeOrder(
            'accept',
            $marketplaceCode,
            $marketplaceOrderId,
            array(),
            $ifMatch,
            $testMode
        );
    }


    public function refuseOrder(
        $marketplaceCode,
        $marketplaceOrderId,
        $ifMatch = "",
        $testMode = false
    ) {
        return $this->changeOrder(
            'refuse',
            $marketplaceCode,
            $marketplaceOrderId,
            array(),
            $ifMatch,
            $testMode
        );
    }

    public function cancelOrder(
        $marketplaceCode,
        $marketplaceOrderId,
        $cancelReason,
        $ifMatch = "",
        $testMode = false
    ) {
        return $this->changeOrder(
            'cancel',
            $marketplaceCode,
            $marketplaceOrderId,
            array('cancellationReason' => $cancelReason),
            $ifMatch,
            $testMode
        );
    }

    public function shipOrder(
        $marketplaceCode,
        $marketplaceOrderId,
        $trackingNumber,
        $carrierCode,
        $ifMatch = "",
        $testMode = false
    ) {
        return $this->changeOrder(
            'ship',
            $marketplaceCode,
            $marketplaceOrderId,
            array(
                'trackingNumber' => $trackingNumber,
                'carrierCode' => $carrierCode
            ),
            $ifMatch,
            $testMode
        );
    }

    public function shipOrderWithTrackingUrl(
        $marketplaceCode,
        $marketplaceOrderId,
        $trackingNumber,
        $carrierCode,
        $trackingUrl,
        $ifMatch = "",
        $testMode = false
    ) {
        return $this->changeOrder(
            'shipWithTrackingUrl',
            $marketplaceCode,
            $marketplaceOrderId,
            array(
                'trackingNumber' => $trackingNumber,
                'carrierCode' => $carrierCode,
                'trackingUrl' => $trackingUrl
            ),
            $ifMatch,
            $testMode
        );
    }


    public function refundOrder(
        $marketplaceCode,
        $marketplaceOrderId,
        $refundReason,
        $ifMatch = "",
        $testMode = false
    ) {
        return $this->changeOrder(
            'refund',
            $marketplaceCode,
            $marketplaceOrderId,
            array(
                'refundReason' => $refundReason,
            ),
            $ifMatch,
            $testMode
        );
    }



}