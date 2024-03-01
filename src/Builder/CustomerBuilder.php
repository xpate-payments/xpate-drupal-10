<?php

namespace Drupal\commerce_ginger\Builder;

use Drupal;
use Drupal\commerce_ginger\Interface\GetAddressStrategy;
use Drupal\commerce_ginger\Interface\GetlangCodeStrategy;
use GingerPluginSdk\Collections\AdditionalAddresses;
use GingerPluginSdk\Collections\PhoneNumbers;
use GingerPluginSdk\Entities\Address;
use GingerPluginSdk\Entities\Customer;
use GingerPluginSdk\Properties\Country;
use GingerPluginSdk\Properties\EmailAddress;
use GingerPluginSdk\Properties\Locale;
use Drupal\commerce_ginger\ComponentRegister;

/**
 * Class CustomerBuilder.
 *
 * This class contain methods for collecting data about customer
 *
 * @package Drupal\commerce_ginger\Builder
 */
class CustomerBuilder extends ClientBuilder
{

    /**
     * @param object $billing_info
     * @param string $address_type
     *
     * @return \GingerPluginSdk\Entities\Address
     */
    public function getAddress(object $billing_info, string $address_type): Address
    {
        return ComponentRegister::get(GetAddressStrategy::class)->getAddress($billing_info, $address_type);
    }

    /**
     * @return string
     */
    public function getLangCode(): string
    {
        return ComponentRegister::get(GetlangCodeStrategy::class)->getLangCode();
    }

    /**
     * @param object $payment
     *
     * @return \GingerPluginSdk\Collections\AdditionalAddresses
     */
    public function getAdditionalAddresses(object $payment): AdditionalAddresses
    {
        return new AdditionalAddresses(
            $this->getAddress($payment, 'customer'),
            $this->getAddress($payment, 'billing')
        );
    }

    /**
     * @param object $payment
     * @param object|null $birthdate
     * @param string|null $gender
     *
     * @return \GingerPluginSdk\Entities\Customer
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public function getCustomerData(
        object $payment,
        object $birthdate = null,
        string $gender = null
    ): Customer {
        /** @var \Drupal\profile\Entity\ProfileInterface $profile */
        $profile = $payment->getOrder()->getBillingProfile();
        $order = $payment->getOrder();

        $customer = $order->getCustomer();
        $billing_address = $profile->get('address')->getValue()[0];
        /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billing_info */
        $billing_info = $profile->get('address')->first();

        $phone = null;
        if ($profile->hasField('field_phone')) {
            $phone = $profile->get('field_phone')->value;
        } elseif ($profile->hasField('telephone')) {
            $phone = $profile->get('telephone')->value;
        }
        $additional_addresses = $this->getAdditionalAddresses($billing_info);

        return new Customer(
            additionalAddresses: $additional_addresses,
            firstName: $billing_info->getGivenName(),
            lastName: $billing_info->getFamilyName(),
            emailAddress: new EmailAddress($order->getEmail()),
            gender: $gender,
            phoneNumbers: new PhoneNumbers($phone ?? ''),
            birthdate: $birthdate,
            addressType: 'billing',
            locale: new Locale($this->getLangCode($payment))
        );
    }
}
