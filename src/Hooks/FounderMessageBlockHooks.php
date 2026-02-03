<?php

declare(strict_types=1);

namespace Drupal\omnipedia_main_page\Hooks;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\hux\Attribute\Alter;
use Drupal\omnipedia_main_page\Service\MainPageCacheInterface;
use Drupal\omnipedia_main_page\Service\MainPageRouteInterface;

/**
 * Founder message block hooks.
 */
class FounderMessageBlockHooks {

  /**
   * Constructor; saves dependencies.
   *
   * @param \Drupal\omnipedia_main_page\Service\MainPageCacheInterface $mainPageCache
   *   The Omnipedia main page cache service.
   *
   * @param \Drupal\omnipedia_main_page\Service\MainPageRouteInterface $mainPageRoute
   *   The Omnipedia main page route service interface.
   */
  public function __construct(
    protected readonly MainPageCacheInterface $mainPageCache,
    protected readonly MainPageRouteInterface $mainPageRoute,
  ) {}

  /**
   * Alter the build arrays for the founder message blocks.
   *
   * This prevents displaying the blocks on non-main pages and adds main page
   * cache metadata.
   */
  #[Alter('block_build_omnipedia_founder_message')]
  #[Alter('block_build_omnipedia_founder_message_join')]
  public function blockBuildAlter(
    array &$build,
    BlockPluginInterface $block,
  ): void {

    // If the current route is not a main page, don't display the block.
    //
    // @todo Can this be exposed as a general option on all blocks so that we
    //   don't have to hard code it here?
    if (!$this->mainPageRoute->isCurrent()) {
      $build['#access'] = false;
    }

    $build['#cache']['contexts'] = Cache::mergeContexts(
      $build['#cache']['contexts'],
      // Vary by whether the current route is a main page.
      ['omnipedia_is_wiki_main_page'],
    );

    $build['#cache']['tags'] = Cache::mergeTags(
      $build['#cache']['tags'],
      $this->mainPageCache->getAllCacheTags(),
    );

  }

}
