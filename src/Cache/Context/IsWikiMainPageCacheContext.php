<?php

declare(strict_types=1);

namespace Drupal\omnipedia_main_page\Cache\Context;

use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\omnipedia_main_page\Service\MainPageRouteInterface;

/**
 * Defines the Omnipedia is wiki main page cache context service.
 *
 * Cache context ID: 'omnipedia_is_wiki_main_page'.
 *
 * This allows for caching to vary on whether the current route is a wiki main
 * page.
 */
class IsWikiMainPageCacheContext implements CalculatedCacheContextInterface {

  /**
   * Service constructor; saves dependencies.
   *
   * @param \Drupal\omnipedia_main_page\Service\MainPageRouteInterface $mainPageRoute
   *   The Omnipedia main page route service interface.
   */
  public function __construct(
    protected readonly MainPageRouteInterface $mainPageRoute,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return \t('Omnipedia is wiki main page');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($parameter = null) {

    if ($this->mainPageRoute->isCurrent()) {
      return 'is_wiki_main_page';
    }

    return 'is_not_wiki_main_page';

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($parameter = null) {
    return new CacheableMetadata();
  }
}
