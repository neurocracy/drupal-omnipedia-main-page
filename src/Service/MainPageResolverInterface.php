<?php

declare(strict_types=1);

namespace Drupal\omnipedia_main_page\Service;

use Drupal\node\NodeInterface;

/**
 * The Omnipedia main page resolver service interface.
 */
interface MainPageResolverInterface {

  /**
   * Determine if a parameter is or equates to a main page wiki node.
   *
   * @param mixed $node
   *   A node entity object or a numeric value (integer or string) that equates
   *   to an existing node ID (nid) to load. Any other value will return false.
   *
   * @return boolean
   *   Returns true if the $node parameter is a main page wiki node or if it is
   *   a numeric value that equates to the ID of a main page wiki node; returns
   *   false otherwise.
   */
  public function is(mixed $node): bool;

  /**
   * Get the main page node for the specified date.
   *
   * @param string $date
   *   Must be one of the following:
   *
   *   - A date string in the format stored in a wiki node's date field
   *
   *   - 'default': alias for the default main page as configured in the site
   *     configuration
   *
   * @return \Drupal\node\NodeInterface|null
   *   Returns the main page's node object for the specified date if it can be
   *   found; returns null otherwise.
   */
  public function get(string $date): ?NodeInterface;

}
