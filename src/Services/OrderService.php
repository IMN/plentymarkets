<?php
/**
 * OrderService.php
 *
 * @author Luis Ferrer <luis@bootdevelop.com>
 */

namespace IMN\Services;


use Plenty\Modules\Account\Address\Contracts\AddressContactRelationRepositoryContract;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Account\Address\Models\AddressRelationType;
use Plenty\Modules\Account\Contact\Contracts\ContactRepositoryContract;
use Plenty\Modules\Account\Contact\Models\Contact;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentContactRelationRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentOrderRelationRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Account\Contact\Models\ContactOption;
use Plenty\Plugin\Application;


class OrderService
{


    private $settings;

    /**
     * @var ContactRepositoryContract
     */
    private $contactRepository;

    /**
     * @var CountryRepositoryContract
     */
    private $countryRepository;

    /**
     * @var OrderRepositoryContract
     */
    private $orderRepository;

    /**
     * @var AddressRepositoryContract
     */
    private $addressRepository;

    /**
     * @var AddressContactRelationRepositoryContract
     */
    private $addressContractRelationRepository;

    /**
     * @var PaymentRepositoryContract
     */
    private $paymentRepository;


    /**
     * @var PaymentOrderRelationRepositoryContract
     */
    private $paymentOrderRelationRepository;

    /**
     * @var PaymentContactRelationRepositoryContract
     */
    private $paymentContactRelationRepository;

    /**
     * @var Application
     */
    private $app;

    private $imnOrderId = "";

    private $identifier = array();

    public function __construct(
        PaymentOrderRelationRepositoryContract $paymentOrderRelationRepositoryContract,
        PaymentContactRelationRepositoryContract $paymentContactRelationRepositoryContract,
        PaymentRepositoryContract $paymentRepositoryContract,
        OrderRepositoryContract $orderRepository,
        ContactRepositoryContract $contactRepository,
        CountryRepositoryContract $countryRepositoryContract,
        AddressRepositoryContract $addressRepositoryContract,
        AddressContactRelationRepositoryContract $addressContactRelationRepository,
        Application $app
    )
    {
        $this->paymentContactRelationRepository = $paymentContactRelationRepositoryContract;
        $this->paymentOrderRelationRepository = $paymentOrderRelationRepositoryContract;
        $this->paymentRepository = $paymentRepositoryContract;
        $this->addressContractRelationRepository = $addressContactRelationRepository;
        $this->app = $app;
        $this->addressRepository = $addressRepositoryContract;
        $this->orderRepository = $orderRepository;
        $this->countryRepository = $countryRepositoryContract;
        $this->contactRepository = $contactRepository;
    }

    public function externalOrderIdExists($externalOrderId) {
        try {
            $order = $this->orderRepository->findOrderByExternalOrderId($externalOrderId);
        } catch(\Exception $ex) {
            return false;
        }

        return (empty($order)) ? false : $order;
    }


    public function setSettings($settings) {
        $this->settings = $settings;
    }


    public function updateOrder(Order $order, $imnOrder, $statusId) {
        $info = $imnOrder['info'];
        $identifier = $info['identifier'];
        $this->identifier = $identifier;
        $this->imnOrderId = $identifier['marketplaceCode']."/".$identifier['merchantCode']."/".$identifier['marketplaceOrderId'];
        $billingInfo = $info['billingInfo'];
        $shippingInfo = $info['shippingInfo'];
        $pricingInfo = $info['pricingInfo'];
        foreach($order->addressRelations as $address) {
            if($address->typeId == AddressRelationType::BILLING_ADDRESS) {
                $this->updateAddress($address->id, $billingInfo['address'], $billingInfo['customer']);
            } else {
                $this->updateAddress($address->id, $shippingInfo['address'], $shippingInfo['contact']);
            }
        }

        $payOrder = false;
        if(!in_array($info['generalInfo']['imnOrderStatus'], array(
            'Cancelled',
            'Pending',
        ))) {
            foreach($order->properties as $property) {
                if($property->typeId == OrderPropertyType::PAYMENT_STATUS
                    && $property->value == 'unpaid'
                ) {
                    $payOrder = true;
                    break;
                }
            }
        }

        $orderRequest = array(
            'statusId' => $statusId,
        );
        $order = $this->orderRepository->updateOrder($orderRequest, $order->id);

        if($payOrder) {
            //$contact = $this->getContact($billingInfo['customer']);
            $contact = $this->getContact($shippingInfo['contact']);
            $this->createPayment($imnOrder, $order, $contact);
        }

        return $order;

    }


    public function createOrder(
        $imnOrder,
        $statusId,
        $orderItems
    ) {



        $info = $imnOrder['info'];
        $payOrder = false;
        if(!in_array($info['generalInfo']['imnOrderStatus'], array(
            'Cancelled',
            'Pending',
        ))) {
            $payOrder = true;
        }

        $identifier = $info['identifier'];
        $this->imnOrderId = $identifier['marketplaceCode']."/".$identifier['merchantCode']."/".$identifier['marketplaceOrderId'];
        $billingInfo = $info['billingInfo'];
        $shippingInfo = $info['shippingInfo'];
        $pricingInfo = $info['pricingInfo'];
        $contact = $this->getContact($billingInfo['customer']);
        $contactId = $contact->id;

        $billingAddressId = $this->getAddressId(
            $contactId,
            $billingInfo['address'],
            $billingInfo['customer'],
            AddressRelationType::BILLING_ADDRESS
        );


        $shippingAddressId = $this->getAddressId(
            $contactId,
            $shippingInfo['address'],
            $shippingInfo['contact'],
            AddressRelationType::DELIVERY_ADDRESS
            );


        $orderRequest = array(
            'referrerId' => $this->settings['orderRefererId']['value'],
            'typeId' => 1,
            'statusId' => $statusId,
            'orderItems' => $orderItems,
            'plentyId' => $this->app->getPlentyId(),
            'properties' => array(
                array(
                    'typeId' => OrderPropertyType::EXTERNAL_ORDER_ID,
                    'value' => $this->imnOrderId
                )
            ),
            'addressRelations' => array(
                array(
                    'typeId' => AddressRelationType::BILLING_ADDRESS, //billing
                    'addressId' => $billingAddressId,
                ),
                array(
                    'typeId' => AddressRelationType::DELIVERY_ADDRESS, //delivery
                    'addressId' => $shippingAddressId,
                )
            ),
            'relations' => array(
                array(
                    'referenceType' => 'contact',
                    'referenceId' => $contactId,
                    'relation' => 'receiver'
                )
            )

        );

        if($payOrder) {
            $orderRequest['properties'][] = array(
                'typeId' => OrderPropertyType::PAYMENT_STATUS,
                'value' => 'paid'
            );
        }

        $order = $this->orderRepository->createOrder($orderRequest);


        if($payOrder) {
            $this->createPayment($imnOrder, $order, $contact);
        }


        return $order;
    }



    private function createPayment(array $imnOrder, Order $order, Contact $contact) {
        $info = $imnOrder['info'];
        $paymentRequest = array(
            'amount' => $info['pricingInfo']['totalPrice'],
            'currency' => $info['pricingInfo']['currencyCode'],
            'type' => 'credit',
            'status' => 2,
            'mopId' => 0,
            'exchangeRatio' => 0,
            'parentId' => 0,
            'transactionType' => 2,
            'hash' => md5($this->imnOrderId)
           // 'regenerateHash' => true
        );

        $payment = $this->paymentRepository->createPayment($paymentRequest);
        $this->paymentOrderRelationRepository->createOrderRelation($payment, $order);
        $this->paymentContactRelationRepository->createContactRelation($payment, $contact);
    }


    private function getContact($imnContact) {
        $contactId = $this->contactRepository->getContactIdByEmail($imnContact['email']);
        if($contactId) {
            return $contactId;
        }
        $phone = (empty($imnContact['phoneNumber'])) ? $imnContact['mobilePhoneNumber'] : $imnContact['phoneNumber'];
        $name = (empty($imnContact['companyName'])) ? $imnContact['firstName']." ".$imnContact['lastName'] : $imnContact['companyName'];

        $contactRequest = array(
            'referrerId' => $this->settings['orderRefererId']['value'],
            'typeId' => 1,
            'firstName' => $imnContact['firstName'],
            'lastName' => $imnContact['lastName'],
            'options' => array(
//                array(
//                    'typeId' => ContactOption::TYPE_PHONE,
//                    'subTypeId' => ContactOption::SUBTYPE_PRIVATE,
//                    'priority' => 0,
//                    'value' => $phone
//                ),
                array(
                    'typeId' => ContactOption::TYPE_ACCESS,
                    'subTypeId' => ContactOption::SUBTYPE_GUEST,
                    'priority' => 0,
                    'value' => '1'
                ),
                array(
                    'typeId' => ContactOption::TYPE_MAIL,
                    'subTypeId' => ContactOption::SUBTYPE_PRIVATE,
                    'priority' => 0,
                    'value' => $imnContact['email']
                )
            )
            //'fullName' => $name,
           // 'email' => $imnContact['email']
        );


        $contact = $this->contactRepository->createContact($contactRequest);

        return $contact;

    }

    private function getCountryId($isoCode) {
        $country = $this->countryRepository->getCountryByIso($isoCode, "isoCode2");
        if(!$country) {
            throw new \Exception("Country with iso code ".$isoCode." does not exist");
        }
        return $country->id;
    }

    private function getStateId($countryId, $stateIso) {
        $countryState = $this->countryRepository->getCountryStateByIso($countryId, $stateIso);
        if(!$countryState) {
            return false;
        }
        return $countryState->id;
    }


    private function updateAddress($addressId, $address, $imnContact) {
        $name = (empty($imnContact['companyName'])) ? $imnContact['firstName']." ".$imnContact['lastName'] : $imnContact['companyName'];
        $phone = (empty($imnContact['phoneNumber'])) ? $imnContact['mobilePhoneNumber'] : $imnContact['phoneNumber'];
        $countryId = $this->getCountryId($address['countryIsoCodeAlpha2']);
        $stateId = $this->getStateId($countryId, $address['stateOrRegion']);
        $request = array(
            'name1' => $name,
            'address1' => $address['line1'],
            'address2' => $address['line2'],
            'town' => $address['city'],
            'postalCode' => $address['postalCode'],
            'phone' => $phone,

            'countryId' => $countryId,
            'useAddressLightValidator' => true,
            'options' => array(
                array(
                    'typeId' => 4,
                    'value' => $phone
                )
            )
        );

        if(array_key_exists('comment', $address) && strlen($address['comment']) > 0) {
            $request['address4'] = $address['comment'];
        }

        if($stateId) {
            $request['stateId'] = $stateId;
        }

        $this->addressRepository->updateAddress($request, $addressId);
    }

    private function getAddressId(
            int $contactId,
            $address,
            $imnContact,
            int $addressRelationType
    ) {

        $name = (empty($imnContact['companyName'])) ? $imnContact['firstName']." ".$imnContact['lastName'] : $imnContact['companyName'];
        $phone = (empty($imnContact['phoneNumber'])) ? $imnContact['mobilePhoneNumber'] : $imnContact['phoneNumber'];
        $countryId = $this->getCountryId($address['countryIsoCodeAlpha2']);
        $stateId = $this->getStateId($countryId, $address['stateOrRegion']);
        $request = array(
            'name1' => $name,
            'address1' => $address['line1'],
            'address2' => $address['line2'],
            'town' => $address['city'],
            'postalCode' => $address['postalCode'],
            'phone' => $phone,
            //'email' => $imnContact['email'],
            'countryId' => $countryId,
            'useAddressLightValidator' => true,
            'options' => array(
                array(
                    'typeId' => 5,
                    'value' => $imnContact['email']
                ),
                array(
                    'typeId' => 4,
                    'value' => $phone
                )
            )
        );

        if(array_key_exists('comment', $address) && strlen($address['comment']) > 0) {
            $request['address4'] = $address['comment'];
        }

        if($stateId) {
            $request['stateId'] = $stateId;
        }


        $address = $this->addressRepository->createAddress($request);

        $this->addressContractRelationRepository->createAddressContactRelation(array(
            array(
                'contactId' => $contactId,
                'addressId' => $address->id,
                'typeId' => $addressRelationType
            )
        ));


        return $address->id;
    }


}