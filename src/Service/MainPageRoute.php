<?php

declare(strict_types=1);

namespace Drupal\omnipedia_main_page\Service;

use Drupal\Core\Routing\StackedRouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\omnipedia_core\Service\WikiNodeRouteInterface;
use Drupal\omnipedia_main_page\Service\MainPageDefaultInterface;
use Drupal\omnipedia_main_page\Service\MainPageResolverInterface;
use Drupal\omnipedia_main_page\Service\MainPageRouteInterface;

/**
 * The Omnipedia main page route service.
 */
class MainPageRoute implements MainPageRouteInterface {

  /**
   * Constructs this service object; saves dependencies.
   *
   * @param \Drupal\Core\Routing\StackedRouteMatchInterface $currentRouteMatch
   *   The Drupal current route match service.
   *
   * @param \Drupal\omnipedia_main_page\Service\MainPageDefaultInterface $mainPageDefault
   *   The Omnipedia default main page service.
   *
   * @param \Drupal\omnipedia_main_page\Service\MainPageResolverInterface $mainPageResolver
   *   The Omnipedia main page resolver service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeRouteInterface $wikiNodeRoute
   *   The Omnipedia wiki node route service.
   */
  public function __construct(
    protected readonly StackedRouteMatchInterface $currentRouteMatch,
    protected readonly MainPageDefaultInterface   $mainPageDefault,
    protected readonly MainPageResolverInterface  $mainPageResolver,
    protected readonly WikiNodeRouteInterface     $wikiNodeRoute,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function isCurrent(): bool {

    // Return false if this route is not considered viewing a wiki node.
    if (!$this->wikiNodeRoute->isWikiNodeViewRouteName(
      $this->currentRouteMatch->getRouteName(),
    )) {
      return false;
    }

    /** @var \Drupal\node\NodeInterface|null */
    $node = $this->currentRouteMatch->getParameter('node');

    return $this->mainPageResolver->is($node);

  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'entity.node.canonical';
  }

  /**
   * {@inheritdoc}
   */
  public function getParameters(string $date): array {

    /** @var \Drupal\node\NodeInterface|null */
    $node = $this->mainPageResolver->get($date);

    // Fall back to the default main page if this date doesn't have one to avoid
    // Drupal throwing an exception if we were to return an empty array.
    if (!($node instanceof NodeInterface)) {
      $node = $this->mainPageDefault->get();
    }

    return ['node' => $node->nid->getString()];

  }

}
