<?php

declare(strict_types=1);

namespace Drupal\Tests\omnipedia_main_page\Kernel;

use Drupal\omnipedia_main_page\Service\MainPageRoute;
use Drupal\Tests\omnipedia_main_page\Kernel\MainPageServiceKernelTestBase;
use Drupal\Core\Routing\StackedRouteMatchInterface;

/**
 * Tests for the Omnipedia main page route service.
 *
 * @group omnipedia
 *
 * @group omnipedia_main_page
 *
 * @coversDefaultClass \Drupal\omnipedia_main_page\Service\MainPageRoute
 */
class MainPageRouteTest extends MainPageServiceKernelTestBase {

  /**
   * Test current route detection.
   *
   * @covers ::isCurrent()
   *
   * @todo Also test node edit and preview route names.
   */
  public function testIsCurrent(): void {

    $nodes = $this->generateTestNodes(true);

    /** @var \Drupal\node\NodeInterface The first node, which is always a wiki node, which we use as the default main page. */
    $firstNode = \reset($nodes);

    /** @var \Drupal\omnipedia_core\WrappedEntities\NodeWithWikiInfoInterface */
    $firstNodeWrapped = $this->typedEntityRepositoryManager->wrap($firstNode);

    $firstDate = $firstNodeWrapped->getWikiDate();

    $mainPageTitle = $firstNodeWrapped->label();

    $this->mainPageDefault->set($firstNode);

    // Assert that non-node route names never match.
    foreach ([
      'node.add', 'system.admin', 'system.status',
      'baby-shark-do-do-do-do', 'nyan-cat', 'bears-bears-bears',
    ] as $routeName) {

      /** @var \Prophecy\Prophecy\ProphecyInterface Mocked up Drupal current route match service. */
      $currentRouteMatch = $this->prophesize(StackedRouteMatchInterface::class);

      $currentRouteMatch->getRouteName()->willReturn($routeName);

      /** @var \Drupal\omnipedia_main_page\Service\MainPageRouteInterface Omnipedia main page route service instance with mocked up current route match. */
      $mainPageRoute = new MainPageRoute(
        $currentRouteMatch->reveal(),
        $this->container->get('omnipedia_main_page.default'),
        $this->container->get('omnipedia_main_page.resolver'),
        $this->container->get('omnipedia.wiki_node_route'),
      );

      $this->assertFalse($mainPageRoute->isCurrent());

    }

    /** @var integer Counter to assert that we actually tested at least one main page. */
    $count = 0;

    foreach ($nodes as $nid => $node) {

      /** @var \Drupal\omnipedia_core\WrappedEntities\NodeWithWikiInfoInterface */
      $wrappedNode = $this->typedEntityRepositoryManager->wrap($node);

      /** @var \Prophecy\Prophecy\ProphecyInterface Mocked up Drupal current route match service. */
      $currentRouteMatch = $this->prophesize(StackedRouteMatchInterface::class);

      $currentRouteMatch->getRouteName()->willReturn('entity.node.canonical');

      $currentRouteMatch->getParameter('node')->willReturn($node);

      /** @var \Drupal\omnipedia_main_page\Service\MainPageRouteInterface Omnipedia main page route service instance with mocked up current route match. */
      $mainPageRoute = new MainPageRoute(
        $currentRouteMatch->reveal(),
        $this->container->get('omnipedia_main_page.default'),
        $this->container->get('omnipedia_main_page.resolver'),
        $this->container->get('omnipedia.wiki_node_route'),
      );

      if ($wrappedNode->label() === $mainPageTitle) {
        $this->assertTrue($mainPageRoute->isCurrent());
      } else {

        $this->assertFalse($mainPageRoute->isCurrent());
      }

      $count++;

    }

    $this->assertGreaterThan(0, $count);

  }

  /**
   * Test that the service returns the expected route name and parameters.
   *
   * Note that ::getName() currently just returns a hard-coded string so rather
   * than test that in a separate method or implement a unit test, we just
   * bundle it into this test for simplicity.
   *
   * @covers ::getName()
   * @covers ::getParameters()
   */
  public function testGetNameAndParameters(): void {

    /** @var \Drupal\omnipedia_main_page\Service\MainPageRouteInterface Omnipedia main page route service instance. */
    $mainPageRoute = $this->container->get(
      'omnipedia_main_page.route',
    );

    $this->assertIsString($mainPageRoute->getName());

    $this->assertEquals(
      'entity.node.canonical', $mainPageRoute->getName(),
    );

    $nodes = $this->generateTestNodes(true);

    /** @var \Drupal\node\NodeInterface The first node, which is always a wiki node, which we use as the default main page. */
    $firstNode = \reset($nodes);

    /** @var \Drupal\omnipedia_core\WrappedEntities\NodeWithWikiInfoInterface */
    $firstNodeWrapped = $this->typedEntityRepositoryManager->wrap($firstNode);

    $firstDate = $firstNodeWrapped->getWikiDate();

    $mainPageTitle = $firstNodeWrapped->label();

    $this->mainPageDefault->set($firstNode);

    /** @var integer Counter to assert that we actually tested at least one main page. */
    $count = 0;

    foreach ($nodes as $nid => $node) {

      // Don't bother wrapping the node if it doesn't match a main page title.
      if ($node->getTitle() !== $mainPageTitle) {
        continue;
      }

      /** @var \Drupal\omnipedia_core\WrappedEntities\NodeWithWikiInfoInterface */
      $wrappedNode = $this->typedEntityRepositoryManager->wrap($node);

      $this->assertEquals(
        $node->id(),
        $mainPageRoute->getParameters(
          $wrappedNode->getWikiDate(),
        )['node'],
      );

      $count++;

    }

    $this->assertGreaterThan(0, $count);

  }

}
