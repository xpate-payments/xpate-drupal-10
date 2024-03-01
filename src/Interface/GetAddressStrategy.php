<?php

namespace Drupal\commerce_ginger\Interface;

use GingerPluginSdk\Entities\Address;

interface GetAddressStrategy extends BaseStrategy
{
  public function getAddress(object $billing_info, string $address_type): Address;
}
