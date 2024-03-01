<?php

namespace Drupal\commerce_ginger\Interface;

interface GetlangCodeStrategy extends BaseStrategy
{
  public function getLangCode(): string;

}
