<?php

namespace Drupal\commerce_ginger\Strategies;

use Drupal\commerce_ginger\Interface\PreparePaymentMethodDetailsStrategy;
use GingerPluginSdk\Entities\PaymentMethodDetails;

class DefaultPreparePaymentMethodDetails implements PreparePaymentMethodDetailsStrategy
{

  public function preparePaymentMethodDetails(string $issuer_id,): PaymentMethodDetails
  {
    {
      return new PaymentMethodDetails(
        array_filter([
          'issuer_id' => $issuer_id,
          //        'verified_terms_of_service' => $verifiedTerms,
          //        'customer' => 'customer'
        ])
      );
    }
  }
}
