<?php

namespace Drupal\commerce_ginger\PSP;

use Drupal\commerce_ginger\ComponentRegister;
use Drupal\commerce_ginger\Interface\GetAddressStrategy;
use Drupal\commerce_ginger\Interface\GetCustomerDataStrategy;
use Drupal\commerce_ginger\Interface\GetIssuersStrategy;
use Drupal\commerce_ginger\Interface\GetlangCodeStrategy;
use Drupal\commerce_ginger\Interface\PreparePaymentMethodDetailsStrategy;
use Drupal\commerce_ginger\Strategies\DefaultGetAddress;
use Drupal\commerce_ginger\Strategies\DefaultGetCustomerData;
use Drupal\commerce_ginger\Strategies\DefaultGetIssuers;
use Drupal\commerce_ginger\Strategies\DefaultGetLangCode;
use Drupal\commerce_ginger\Strategies\DefaultPreparePaymentMethodDetails;
use Symfony\Component\Yaml\Yaml;

/**
 * Class PSP.
 *
 * This class contain configs for a PSP
 *
 * @package Drupal\commerce_ginger\Builder
 */
class PSPconfig
{

    const PLATFORM_NAME = 'Drupal10';

    const PLUGIN_NAME = 'XPATE-Drupal10';

    const ENDPOINT = 'https://api.gateway.xpate.com';

    const LOGGER_CHANEL = 'example_plugin';

    /**
     * @return mixed|string
     */
    public static function getPluginVersion(): mixed
    {
        $pluginInfo = Yaml::parseFile(
            DRUPAL_ROOT.'/modules/XPATE-payment-plugin/commerce_ginger.info.yml'
        );

        return $pluginInfo['version'] ?? '1.0.0';
    }

    /**
     * @return string
     */
    public static function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    /**
     * @return string
     */
    public static function getPlatformName(): string
    {
        return self::PLATFORM_NAME;
    }

    /**
     * @return string
     */
    public static function getEndpoint(): string
    {
        return self::ENDPOINT;
    }


    /**
     * @return string
     */
    public static function getLoggerChanel(): string
    {
        return self::LOGGER_CHANEL;
    }

    /**
     * @param  string  $lang
     *
     * @return string
     */
    public static function getAfterPayTermsLink(string $lang): string
    {
        $lang == 'nl' ?
            $link = 'https://www.afterpay.nl/nl/algemeen/betalen-met-afterpay/betalingsvoorwaarden' :
            $link = 'https://www.afterpay.nl/en/about/pay-with-afterpay/payment-conditions';

        return $link;
    }

    static public function registerStrategies()
    {
        ComponentRegister::register(GetIssuersStrategy::class,new DefaultGetIssuers());
        ComponentRegister::register(GetAddressStrategy::class,new DefaultGetAddress());
        ComponentRegister::register(GetlangCodeStrategy::class,new DefaultGetLangCode());
        ComponentRegister::register(PreparePaymentMethodDetailsStrategy::class,new DefaultPreparePaymentMethodDetails());


    }

}
