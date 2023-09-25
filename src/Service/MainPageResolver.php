<?php

declare(strict_types=1);

namespace Drupal\omnipedia_main_page\Service;

use Drupal\node\NodeInterface;
use Drupal\omnipedia_core\Service\WikiNodeResolverInterface;
use Drupal\omnipedia_core\Service\WikiNodeRevisionInterface;
use Drupal\omnipedia_main_page\Service\MainPageDefaultInterface;
use Drupal\omnipedia_main_page\Service\MainPageResolverInterface;

/**
 * The Omnipedia main page resolver service.
 */
class MainPageResolver implements MainPageResolverInterface {

  /**
   * Constructs this service object; saves dependencies.
   *
   * @param \Drupal\omnipedia_main_page\Service\MainPageDefaultInterface $mainPageDefault
   *   The Omnipedia default main page service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeResolverInterface $wikiNodeResolver
   *   The Omnipedia wiki node resolver service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeRevisionInterface $wikiNodeRevision
   *   The Omnipedia wiki node revision service.
   */
  public function __construct(
    protected readonly MainPageDefaultInterface   $mainPageDefault,
    protected readonly WikiNodeResolverInterface  $wikiNodeResolver,
    protected readonly WikiNodeRevisionInterface  $wikiNodeRevision,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function is(mixed $node): bool {

    /** @var \Drupal\node\NodeInterface|null */
    $node = $this->wikiNodeResolver->resolveWikiNode($node);

    // Return false if this is not a wiki node.
    if (\is_null($node)) {
      return false;
    }

    /** @var array */
    $mainPageNids = $this->wikiNodeResolver->nodeOrTitleToNids(
      $this->mainPageDefault->get(),
    );

    return \in_array($node->nid->getString(), $mainPageNids);

  }

  /**
   * {@inheritdoc}
   */
  public function get(string $date): ?NodeInterface {

    try {

      /** @var \Drupal\node\NodeInterface */
      $default = $this->mainPageDefault->get();

    } catch (\Exception $exception) {

      return null;

    }

    if ($date === 'default') {
      return $default;
    }

    return $this->wikiNodeRevision->getWikiNodeRevision($default, $date);

  }

}
