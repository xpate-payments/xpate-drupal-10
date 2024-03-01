<?php

namespace Drupal\commerce_ginger\Strategies;

use Drupal\commerce_ginger\Builder\ClientBuilder;
use Drupal\commerce_ginger\Interface\GetIssuersStrategy;

class DefaultGetIssuers implements GetIssuersStrategy
{

  public function getIssuers(): array
  {
    $clientBuilder = new ClientBuilder();
    $client = $clientBuilder->createClient();
    return $client->getIdealIssuers()->toArray();
  }
}
