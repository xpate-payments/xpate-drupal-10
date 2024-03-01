<?php

namespace Drupal\commerce_ginger;

class ComponentRegister
{
  protected static $components = [];

  public static function register(string $key, object $component): void
  {
    self::$components[$key] = $component;
  }

  /**
   * @template T of BaseStrategy
   * @param class-string<T> $key
   * @return T|null
   */
  public static function get(string $key)
  {
    return self::$components[$key] ?? null;
  }
}
