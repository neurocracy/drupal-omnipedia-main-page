<?php

declare(strict_types=1);

namespace Drupal\omnipedia_main_page\Service;

use Drupal\node\NodeInterface;

/**
 * The Omnipedia default main page service interface.
 */
interface MainPageDefaultInterface {

  /**
   * Set the default main page.
   *
   * @param \Drupal\node\NodeInterface|string|int $nodeOrNid
   *   A wiki node entity or a node ID (nid) that can be resolved to one.
   *
   * @throws \UnexpectedValueException
   *   If the $nodeOrNid parameter can't be resolved to a wiki node.
   *
   * @todo Remove string and int parameter types; only accept NodeInterface or
   *   a wrapped entity implementing NodeWithWikiInfoInterface.
   */
  public function set(NodeInterface|string|int $nodeOrNid): void;

  /**
   * Get the default main page node.
   *
   * @return \Drupal\node\NodeInterface
   *
   * @throws \UnexpectedValueException
   *   If the default main page has not been set.
   */
  public function get(): NodeInterface;

}
