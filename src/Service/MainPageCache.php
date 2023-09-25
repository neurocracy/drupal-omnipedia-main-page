<?php

declare(strict_types=1);

namespace Drupal\omnipedia_main_page\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\omnipedia_core\Service\WikiNodeResolverInterface;
use Drupal\omnipedia_main_page\Service\MainPageCacheInterface;
use Drupal\omnipedia_main_page\Service\MainPageDefaultInterface;

/**
 * The Omnipedia main page cache service.
 */
class MainPageCache implements MainPageCacheInterface {

  /**
   * The Drupal cache ID where we store their computed cache IDs. (So meta.)
   */
  protected const CACHE_TAGS_ID = 'omnipedia.main_pages_tags';

  /**
   * Constructs this service object; saves dependencies.
   *
   * @param Drupal\Core\Cache\CacheBackendInterface $cache
   *   The default Drupal cache bin.
   *
   * @param \Drupal\omnipedia_main_page\Service\MainPageDefaultInterface $mainPageDefault
   *   The Omnipedia default main page service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeResolverInterface $wikiNodeResolver
   *   The Omnipedia wiki node resolver service.
   */
  public function __construct(
    protected readonly CacheBackendInterface      $cache,
    protected readonly MainPageDefaultInterface   $mainPageDefault,
    protected readonly WikiNodeResolverInterface  $wikiNodeResolver,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getAllCacheTags(): array {

    /** @var object|false */
    $cache = $this->cache->get(self::CACHE_TAGS_ID);

    // If the computed tags are available in the cache, return those.
    if ($cache !== false) {
      return $cache->data;
    }

    /** @var array */
    $nids = $this->wikiNodeResolver->nodeOrTitleToNids(
      /** @var \Drupal\node\NodeInterface */
      $this->mainPageDefault->get(),
    );

    /** @var array */
    $tags = [];

    foreach ($nids as $nid) {

      /** @var \Drupal\node\NodeInterface|null */
      $node = $this->wikiNodeResolver->resolveNode($nid);

      if ($node === null) {
        continue;
      }

      /** @var array */
      $tags = Cache::mergeTags($tags, $node->getCacheTags());

    }

    // Save the computed tags into the cache. We also use the tags as their own
    // cache tags, which is super meta.
    $this->cache->set(
      self::CACHE_TAGS_ID,
      $tags,
      CacheBackendInterface::CACHE_PERMANENT,
      $tags,
    );

    return $tags;

  }

}
