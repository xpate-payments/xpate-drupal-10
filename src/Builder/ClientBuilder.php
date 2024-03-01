<?php

namespace Drupal\commerce_ginger\Builder;

use Drupal;
use GingerPluginSdk\Entities\Client as EntitiesClient;
use GingerPluginSdk\Client;
use GingerPluginSdk\Properties\ClientOptions;
use Drupal\commerce_ginger\PSP\PSPconfig;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ClientBuilder.
 *
 * This class contain methods for getting ExtraLines and EntitiesClient
 *
 * @package Drupal\commerce_ginger\Builder
 */
class ClientBuilder
{

  use StringTranslationTrait;

  /**
   * @var string
   */
  private mixed $apiKey;

  /**
   * @var Client
   */
  private Client $client;

  public function __construct()
  {
    $settings = Drupal::config('commerce_ginger.settings');
    if (!$settings->get('api_key')) {
      \Drupal::messenger()->addWarning(
        $this->t('Api-Key is missing. Set Api-key in plugin configuration')
      );
    } else {
      $this->apiKey = $settings->get('api_key');
      $this->client = $this->createClient();
    }
  }

  /**
   * @return \GingerPluginSdk\Client
   */
  public function createClient(): Client
  {
    return new Client(
      new ClientOptions(
        endpoint: PSPconfig::getEndpoint(),
        useBundle: true,
        apiKey: $this->apiKey
      )
    );
  }

  /**
   * Return api-key
   *
   * @return string
   */
  public function getApiKey(): string
  {
    return $this->apiKey;
  }

  /**
   * Return Client
   *
   * @return Client
   */
  public function getClient(): Client
  {
    return $this->client;
  }

  /**
   * Customer user agent for API
   *
   * @return mixed
   */
  public function getUserAgent(): mixed
  {
    return $_SERVER['HTTP_USER_AGENT'] ?? null;
  }

}
