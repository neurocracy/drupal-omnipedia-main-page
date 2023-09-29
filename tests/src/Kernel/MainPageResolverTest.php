<?php

declare(strict_types=1);

namespace Drupal\Tests\omnipedia_main_page\Kernel;

use Drupal\omnipedia_main_page\Service\MainPageResolverInterface;
use Drupal\Tests\omnipedia_main_page\Kernel\MainPageServiceKernelTestBase;

/**
 * Tests for the Omnipedia main page resolver service.
 *
 * @group omnipedia
 *
 * @group omnipedia_main_page
 *
 * @coversDefaultClass \Drupal\omnipedia_main_page\Service\MainPageResolver
 */
class MainPageResolverTest extends MainPageServiceKernelTestBase {

  /**
   * The Omnipedia main page resolver service.
   *
   * @var \Drupal\omnipedia_main_page\Service\MainPageResolverInterface
   */
  protected readonly MainPageResolverInterface $mainPageResolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $this->mainPageResolver = $this->container->get(
      'omnipedia_main_page.resolver',
    );

  }

  /**
   * Test that the service correctly identifies main pages and non-main pages.
   *
   * @covers ::is()
   *
   * @todo Test with other wiki nodes in the first date as the default.
   */
  public function testIs(): void {

    $nodes = $this->generateTestNodes(true);

    /** @var \Drupal\node\NodeInterface The first node, which is always a wiki node, which we use as the default main page. */
    $firstNode = \reset($nodes);

    /** @var \Drupal\omnipedia_core\WrappedEntities\NodeWithWikiInfoInterface */
    $firstNodeWrapped = $this->typedEntityRepositoryManager->wrap($firstNode);

    $firstDate = $firstNodeWrapped->getWikiDate();

    $mainPageTitle = $firstNodeWrapped->label();

    $this->mainPageDefault->set($firstNode);

    foreach ($nodes as $nid => $node) {

      /** @var \Drupal\omnipedia_core\WrappedEntities\NodeWithWikiInfoInterface */
      $wrappedNode = $this->typedEntityRepositoryManager->wrap($node);

      // Non-wiki nodes or wiki nodes with a different title from the default
      // main page are expected to return false.
      if (
        $wrappedNode->isWikiNode() === false ||
        $wrappedNode->label() !== $mainPageTitle
      ) {

        $this->assertFalse($this->mainPageResolver->is($node));

        $this->assertFalse($this->mainPageResolver->is((string) $node->id()));

        $this->assertFalse($this->mainPageResolver->is((int) $node->id()));

        continue;

      }

      // What remains should all be main page nodes, which are expected to
      // return true.

      $this->assertTrue($this->mainPageResolver->is($node));

      $this->assertTrue($this->mainPageResolver->is((string) $node->id()));

      $this->assertTrue($this->mainPageResolver->is((int) $node->id()));

    }

  }

  /**
   * Test that the service returns the expected main pages for each date.
   *
   * @covers ::get()
   *
   * @todo Test with other wiki nodes in the first date as the default.
   */
  public function testGet(): void {

    $nodes = $this->generateTestNodes(true);

    /** @var \Drupal\node\NodeInterface The first node, which is always a wiki node, which we use as the default main page. */
    $firstNode = \reset($nodes);

    /** @var \Drupal\omnipedia_core\WrappedEntities\NodeWithWikiInfoInterface */
    $firstNodeWrapped = $this->typedEntityRepositoryManager->wrap($firstNode);

    $firstDate = $firstNodeWrapped->getWikiDate();

    $mainPageTitle = $firstNodeWrapped->label();

    $this->assertNull($this->mainPageResolver->get($firstDate));

    $this->assertNull($this->mainPageResolver->get('default'));

    $this->mainPageDefault->set($firstNode);

    $this->assertEquals(
      $firstNode->id(),
      $this->mainPageResolver->get('default')->id(),
    );

    foreach ($nodes as $nid => $node) {

      /** @var \Drupal\omnipedia_core\WrappedEntities\NodeWithWikiInfoInterface */
      $wrappedNode = $this->typedEntityRepositoryManager->wrap($node);

      if ($wrappedNode->label() !== $mainPageTitle) {
        continue;
      }

      $this->assertEquals(
        $node->id(),
        $this->mainPageResolver->get($wrappedNode->getWikiDate())->id(),
      );

    }

  }

}
