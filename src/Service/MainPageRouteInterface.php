<?php

declare(strict_types=1);

namespace Drupal\omnipedia_main_page\Service;

/**
 * The Omnipedia main page route service interface.
 */
interface MainPageRouteInterface {

  /**
   * Determine if the current route is a main page wiki node.
   *
   * @return boolean
   *   True if the current route is a main page wiki node; false otherwise.
   */
  public function isCurrent(): bool;

  /**
   * Get the main page route name.
   *
   * @return string
   *   The main page route name.
   */
  public function getName(): string;

  /**
   * Get the main page route parameters.
   *
   * @param string $date
   *   The date to build the route parameters for. Must be one of the following:
   *
   *   - A date string in the format stored in a wiki node's date field.
   *
   *   - 'default': alias for the default main page as configured in the site
   *     configuration.
   *
   * @return array
   *   The main page route parameters for the given date, or for the default
   *   main page if a main page does not exist for the given date.
   */
  public function getParameters(string $date): array;

}
