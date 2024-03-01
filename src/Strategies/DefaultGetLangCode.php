<?php

namespace Drupal\commerce_ginger\Strategies;

use Drupal;
use Drupal\commerce_ginger\Interface\GetlangCodeStrategy;

class DefaultGetLangCode implements GetlangCodeStrategy
{

  public function getLangCode(): string
  {
    return Drupal::languageManager()->getCurrentLanguage()->getId();
  }
}
