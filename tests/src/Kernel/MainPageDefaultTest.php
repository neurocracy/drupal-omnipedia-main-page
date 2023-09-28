<?php

declare(strict_types=1);

namespace Drupal\Tests\omnipedia_main_page\Kernel;

use Drupal\omnipedia_core\Service\WikiNodeTrackerInterface;
use Drupal\omnipedia_main_page\Service\MainPageDefaultInterface;
use Drupal\Tests\omnipedia_main_page\Kernel\MainPageServiceKernelTestBase;
use Drupal\typed_entity\EntityWrapperInterface;

/**
 * Tests for the Omnipedia default main page service.
 *
 * @group omnipedia
 *
 * @group omnipedia_main_page
 *
 * @coversDefaultClass \Drupal\omnipedia_main_page\Service\MainPageDefault
 */
class MainPageDefaultTest extends MainPageServiceKernelTestBase {

  /**
   * Test that getting the default main page when not set throws an exception.
   *
   * @covers ::get()
   */
  public function testGetNotSet(): void {

    $this->expectException(\UnexpectedValueException::class);

    $this->mainPageDefault->get();

  }

  /**
   * Test that setting invalid values throws exceptions.
   *
   * @covers ::set()
   */
  public function testSetInvalid(): void {

    $this->expectException(\UnexpectedValueException::class);

    // Passing a negative integer should throw an exception.
    $this->mainPageDefault->set(-1);

    $this->expectException(\UnexpectedValueException::class);

    // Passing a non-numeric string should throw an exception.
    $this->mainPageDefault->set('baby-shark-do-do-do-do');

    $this->expectException(\UnexpectedValueException::class);

    /** @var \Drupal\node\NodeInterface */
    $node = $this->drupalCreateNode(['type' => 'page']);

    // Passing a non-wiki node should throw an exception.
    $this->mainPageDefault->set($node);

  }

  /**
   * Test that setting and getting with valid values works as expected.
   *
   * @covers ::set()
   *
   * @covers ::get()
   */
  public function testSetGetValid(): void {

    $nodes = $this->generateTestNodes(false);

    /** @var \Drupal\node\NodeInterface */
    $firstNode = \reset($nodes);

    /** @var \Drupal\omnipedia_core\WrappedEntities\NodeWithWikiInfoInterface */
    $wrappedNode = $this->typedEntityRepositoryManager->wrap($firstNode);

    $firstDate = $wrappedNode->getWikiDate();

    foreach ($nodes as $nid => $node) {

      /** @var \Drupal\omnipedia_core\WrappedEntities\NodeWithWikiInfoInterface */
      $wrappedNode = $this->typedEntityRepositoryManager->wrap($node);

      // Only use the first date and break afterwards because a main page must
      // exist for all dates unlike other wiki nodes which can start at any
      // date.
      if ($wrappedNode->getWikiDate() !== $firstDate) {
        break;
      }

      $this->mainPageDefault->set($node);

      $this->assertEquals($node->id(), $this->mainPageDefault->get()->id());

      $this->mainPageDefault->set($node->id());

      $this->assertEquals($node->id(), $this->mainPageDefault->get()->id());

    }

  }

}
