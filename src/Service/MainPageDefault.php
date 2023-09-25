<?php

declare(strict_types=1);

namespace Drupal\omnipedia_main_page\Service;

use Drupal\Core\State\StateInterface;
use Drupal\node\NodeInterface;
use Drupal\omnipedia_core\Service\WikiNodeResolverInterface;
use Drupal\omnipedia_main_page\Service\MainPageDefaultInterface;

/**
 * The Omnipedia default main page service.
 */
class MainPageDefault implements MainPageDefaultInterface {

  /**
   * The Drupal state key where we store the node ID of the default main page.
   */
  protected const STATE_KEY = 'omnipedia.default_main_page';

  /**
   * Constructs this service object; saves dependencies.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeResolverInterface $wikiNodeResolver
   *   The Omnipedia wiki node resolver service.
   *
   * @param \Drupal\Core\State\StateInterface $stateManager
   *   The Drupal state system manager.
   */
  public function __construct(
    protected readonly WikiNodeResolverInterface  $wikiNodeResolver,
    protected readonly StateInterface             $stateManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function set(NodeInterface|string|int $nodeOrNid): void {

    /** @var \Drupal\node\NodeInterface|null */
    $node = $this->wikiNodeResolver->resolveWikiNode($nodeOrNid);

    if (\is_null($node)) {

      throw new \UnexpectedValueException(
        'Could not resolve the provided value to a wiki node.',
      );

    }

    $this->stateManager->set(self::STATE_KEY, $node->nid->getString());

  }

  /**
   * {@inheritdoc}
   */
  public function get(): NodeInterface {

    /** @var \Drupal\node\NodeInterface|null */
    $node = $this->wikiNodeResolver->resolveNode($this->stateManager->get(
      self::STATE_KEY,
    ));

    if (\is_null($node)) {

      throw new \UnexpectedValueException(
        'No default main page has been set!',
      );

    }

    return $node;

  }

}
