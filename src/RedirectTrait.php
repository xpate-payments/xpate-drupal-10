<?php

namespace Drupal\commerce_ginger;

use Drupal;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

/**
 * Trait RedirectTrait
 *
 * @package Drupal\commerce_ginger
 */
trait RedirectTrait {

  /**
   * Get Response
   *
   * @param string $url
   *   Url string.
   *
   * @return \Psr\Http\Message\ResponseInterface|string
   *   Response.
   */
  public function getResponse($url) {

    // Your code.

    return $response;
  }

}
