<?php

declare(strict_types=1);

namespace Drupal\omnipedia_main_page\Hooks;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\hux\Attribute\Alter;
use Drupal\omnipedia_date\Service\TimelineInterface;
use Drupal\omnipedia_main_page\Service\MainPageCacheInterface;

/**
 * Adds cache contexts and tags to the 'system_branding_block' block.
 *
 * Our theme requires these changes to cache contexts and tags but cannot do
 * so as preprocess functions are too late in the rendering process.
 */
class SystemBrandingBlockHooks {

  /**
   * Constructor; saves dependencies.
   *
   * @param \Drupal\omnipedia_main_page\Service\MainPageCacheInterface $mainPageCache
   *   The Omnipedia main page cache service.
   *
   * @param \Drupal\omnipedia_date\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   */
  public function __construct(
    protected readonly MainPageCacheInterface $mainPageCache,
    protected readonly TimelineInterface $timeline,
  ) {}

  /**
   * Alter the 'system_branding_block' build array.
   */
  #[Alter('block_build_system_branding_block')]
  public function blockBuildAlter(
    array &$build,
    BlockPluginInterface $block,
  ): void {

    // Vary by the Omnipedia date, user permissions, and user node grants cache
    // contexts.
    //
    // @todo Can most or all of these be fetched from the loaded main page for
    //   the current date?
    $build['#cache']['contexts'] = Cache::mergeContexts(
      $build['#cache']['contexts'],
      ['omnipedia_dates', 'user.permissions', 'user.node_grants:view'],
    );

    // Add the current date cache tag and cache tags from all main pages.
    foreach ([
      ['omnipedia_dates:' . $this->timeline->getDateFormatted(
        'current', 'storage',
      )],
      $this->mainPageCache->getAllCacheTags(),
    ] as $tags) {

      $build['#cache']['tags'] = Cache::mergeTags(
        $build['#cache']['tags'],
        $tags,
      );

    }

  }

}
