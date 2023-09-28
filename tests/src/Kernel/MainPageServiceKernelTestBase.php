<?php

declare(strict_types=1);

namespace Drupal\Tests\omnipedia_main_page\Kernel;

use Drupal\omnipedia_core\Service\WikiNodeTrackerInterface;
use Drupal\omnipedia_main_page\Service\MainPageDefaultInterface;
use Drupal\Tests\omnipedia_core\Kernel\WikiNodeKernelTestBase;
use Drupal\Tests\omnipedia_core\Traits\WikiNodeProvidersTrait;
use Drupal\typed_entity\EntityWrapperInterface;

/**
 * Base class for Omnipedia main page service kernel tests.
 */
abstract class MainPageServiceKernelTestBase extends WikiNodeKernelTestBase {

  use WikiNodeProvidersTrait;

  /**
   * The Omnipedia default main page service.
   *
   * @var \Drupal\omnipedia_main_page\Service\MainPageDefaultInterface
   */
  protected readonly MainPageDefaultInterface $mainPageDefault;

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
   *   Expands on this to include the omnipedia_main_page module.
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

  }

  /**
   * Build and return wiki nodes and optionally non-wiki nodes.
   *
   * @param bool $includeNonWiki
   *   Whether to randomly insert non-wiki nodes.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Node objects keyed by their nid.
   */
  protected function generateTestNodes(bool $includeNonWiki): array {

    $parameters = static::generateWikiNodeValues();

    /** @var \Drupal\node\NodeInterface[] Node objects keyed by their nid. */
    $nodes = [];

    foreach ($parameters as $values) {

      /** @var \Drupal\node\NodeInterface */
      $node = $this->drupalCreateNode($values);

      $this->wikiNodeTracker->trackWikiNode($node);

      $nodes[$node->id()] = $node;

      if ($includeNonWiki === false) {
        continue;
      }

      // Roughly 1 out of 3 times, insert a non-wiki 'page' node type.
      if (\rand(1, 3) === 1) {

        /** @var \Drupal\node\NodeInterface */
        $node = $this->drupalCreateNode(['type' => 'page']);

        $nodes[$node->id()] = $node;

      }

    }

    return $nodes;

  }

}
