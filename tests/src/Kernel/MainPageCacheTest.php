<?php

declare(strict_types=1);

namespace Drupal\Tests\omnipedia_main_page\Kernel;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\omnipedia_main_page\Service\MainPageCache;
use Drupal\Tests\omnipedia_main_page\Kernel\MainPageServiceKernelTestBase;

/**
 * Tests for the Omnipedia main page cache service.
 *
 * @group omnipedia
 *
 * @group omnipedia_main_page
 *
 * @coversDefaultClass \Drupal\omnipedia_main_page\Service\MainPageCache
 */
class MainPageCacheTest extends MainPageServiceKernelTestBase {

  /**
   * Get the cache tags cache ID the main page cache service uses.
   *
   * @return string
   */
  protected function getCacheTagsCid(): string {

    $reflection = new \ReflectionClass(MainPageCache::class);

    return $reflection->getConstant('CACHE_TAGS_ID');

  }

  /**
   * Test getting all main page cache tags when a value is already cached.
   *
   * The service will just return whatever value it finds without validating or
   * altering it, since it does all that before writing it to the cache and it's
   * not expected to be modified by anything outside of the service. We use this
   * to our advantage in this test to keep it as simple as possible without
   * having to create any nodes or do anything complex.
   *
   * @covers ::getAllCacheTags()
   */
  public function testGetAllCacheTagsCached(): void {

    /** @var \Prophecy\Prophecy\ProphecyInterface Mocked up Drupal cache bin. */
    $cache = $this->prophesize(CacheBackendInterface::class);

    $cacheData = new \stdClass();

    $cacheData->data = ['hello'];

    $cache->get($this->getCacheTagsCid())->willReturn($cacheData);

    /** @var \Drupal\omnipedia_main_page\Service\MainPageCacheInterface Omnipedia main page cache service instance with mocked up cache bin. */
    $mainPageCache = new MainPageCache(
      $cache->reveal(),
      $this->container->get('omnipedia_main_page.default'),
      $this->container->get('omnipedia.wiki_node_resolver'),
    );

    $this->assertEquals(['hello'], $mainPageCache->getAllCacheTags());

  }

}
