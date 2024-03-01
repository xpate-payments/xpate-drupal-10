<?php

namespace Drupal\commerce_ginger\Strategies;

use Drupal\commerce_ginger\Interface\GetAddressStrategy;
use GingerPluginSdk\Entities\Address;
use GingerPluginSdk\Properties\Country;

class DefaultGetAddress implements GetAddressStrategy
{

  public function getAddress(object $billing_info, string $address_type): Address
  {
    return new Address(
      addressType: $address_type,
      postalCode: $billing_info->getPostalCode(),
      country: new Country($billing_info->getCountryCode()),
      street: $billing_info->getAddressLine1(),
      city: $billing_info->getLocality()
    );
  }
}
