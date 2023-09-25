<?php

declare(strict_types=1);

namespace Drupal\omnipedia_main_page\Service;

/**
 * The Omnipedia main page cache service interface.
 */
interface MainPageCacheInterface {

  /**
   * Get cache tags for all main pages.
   *
   * @return array
   *   Cache tags for all main pages and any additional data related to them.
   */
  public function getAllCacheTags(): array;

}
