<?php

declare(strict_types=1);

namespace Drupal\Tests\omnipedia_main_page\Kernel;

use Drupal\omnipedia_core\Service\WikiNodeTrackerInterface;
use Drupal\omnipedia_main_page\Service\MainPageDefaultInterface;
use Drupal\Tests\omnipedia_core\Kernel\WikiNodeKernelTestBase;
use Drupal\Tests\omnipedia_core\Traits\WikiNodeProvidersTrait;
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
class MainPageDefaultTest extends WikiNodeKernelTestBase {

  use WikiNodeProvidersTrait;

  /**
   * The Omnipedia default main page service.
   *
   * @var \Drupal\omnipedia_main_page\Service\MainPageDefaultInterface
   */
  protected readonly MainPageDefaultInterface $mainPageDefault;

  /**
   * Node objects for the tests, keyed by their nid.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected array $nodes;

  /**
   * The Typed Entity repository manager.
   *
   * @var \Drupal\typed_entity\EntityWrapperInterface
   */
  protected readonly EntityWrapperInterface $typedEntityRepositoryManager;

  /**
   * The Omnipedia wiki node tracker service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeTrackerInterface
   */
  protected readonly WikiNodeTrackerInterface $wikiNodeTracker;

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Tests\omnipedia_core\Kernel\WikiNodeKernelTestBase::$modules
   */
  protected static $modules = [
    'datetime', 'field', 'filter', 'menu_ui', 'node', 'omnipedia_core',
    'omnipedia_date', 'omnipedia_main_page', 'system', 'taxonomy', 'text',
    'typed_entity', 'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $this->mainPageDefault = $this->container->get(
      'omnipedia_main_page.default',
    );

    $this->typedEntityRepositoryManager = $this->container->get(
      'Drupal\typed_entity\RepositoryManager',
    );

    $this->wikiNodeTracker = $this->container->get(
      'omnipedia.wiki_node_tracker',
    );
    $this->drupalCreateContentType(['type' => 'page']);

    $parameters = static::generateWikiNodeValues();

    /** @var \Drupal\node\NodeInterface[] Node objects keyed by their nid. */
    $nodes = [];

    foreach ($parameters as $values) {

      /** @var \Drupal\node\NodeInterface */
      $node = $this->drupalCreateNode($values);

      $this->wikiNodeTracker->trackWikiNode($node);

      $nodes[$node->id()] = $node;

    }

    $this->nodes = $nodes;

  }

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

    /** @var \Drupal\node\NodeInterface */
    $node = \reset($this->nodes);

    /** @var \Drupal\omnipedia_core\WrappedEntities\NodeWithWikiInfoInterface */
    $wrappedNode = $this->typedEntityRepositoryManager->wrap($node);

    $firstDate = $wrappedNode->getWikiDate();

    foreach ($this->nodes as $nid => $node) {

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
