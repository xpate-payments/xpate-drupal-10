<?php

namespace Drupal\commerce_ginger\Interface;

use GingerPluginSdk\Entities\PaymentMethodDetails;

interface PreparePaymentMethodDetailsStrategy extends BaseStrategy
{
  public function preparePaymentMethodDetails(
    string $issuer_id,
  ): PaymentMethodDetails;
}
