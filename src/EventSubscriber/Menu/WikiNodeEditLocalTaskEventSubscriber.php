<?php

declare(strict_types=1);

namespace Drupal\omnipedia_main_page\EventSubscriber\Menu;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\core_event_dispatcher\Event\Menu\MenuLocalTasksAlterEvent;
use Drupal\core_event_dispatcher\MenuHookEvents;
use Drupal\Core\Routing\StackedRouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\omnipedia_core\Service\WikiNodeResolverInterface;
use Drupal\omnipedia_main_page\Service\MainPageResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to show the wiki node edit local task even without access.
 */
class WikiNodeEditLocalTaskEventSubscriber implements EventSubscriberInterface {

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\Routing\StackedRouteMatchInterface $currentRouteMatch
   *   The Drupal current route match service.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The Drupal current user account proxy service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeMainPageInterface $wikiNodeMainPage
   *   The Omnipedia wiki node main page service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeResolverInterface $wikiNodeResolver
   *   The Omnipedia wiki node resolver service.
   */
  public function __construct(
    protected readonly StackedRouteMatchInterface $currentRouteMatch,
    protected readonly AccountProxyInterface      $currentUser,
    protected readonly MainPageResolverInterface  $mainPageResolver,
    protected readonly WikiNodeResolverInterface  $wikiNodeResolver,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MenuHookEvents::MENU_LOCAL_TASKS_ALTER => 'onMenuLocalTaskAlter',
    ];
  }

  /**
   * Alter local tasks.
   *
   * @param \Drupal\core_event_dispatcher\Event\Menu\MenuLocalTasksAlterEvent $event
   *   Event object.
   */
  public function onMenuLocalTaskAlter(MenuLocalTasksAlterEvent $event): void {

    /** @var array Menu local tasks data. */
    $data = &$event->getData();

    // Don't do anything if the edit tab isn't present. This will be present
    // even if a user doesn't have access to it, but not rendered by default if
    // said user does not have access to the route it points to.
    if (!isset($data['tabs'][0]['entity.node.edit_form'])) {
      return;
    }

    // If there's a 'node' route parameter, attempt to resolve it to a wiki
    // node. Note that the 'node' parameter is not upcast into a Node object if
    // viewing a (Drupal) revision other than the currently published one.
    /** @var \Drupal\node\NodeInterface|null */
    $node = $this->wikiNodeResolver->resolveWikiNode(
      $this->currentRouteMatch->getParameter('node'),
    );

    if (
      $node === null ||
      // If a user has update access to the wiki node, allow the usual access
      // checks to run. This allows users with update access to see the edit
      // tab on main pages, which would otherwise be hidden by the subsequent
      // checks.
      $node->access('update', $this->currentUser) === true
    ) {
      return;
    }

    /** @var \Drupal\Core\Access\AccessResultInterface */
    $accessResult = AccessResult::allowedIf(
      !$this->mainPageResolver->is($node),
    );

    /** @var array */
    $tab = &$data['tabs'][0]['entity.node.edit_form'];

    // Combine our access result with an existing one if it exists.
    //
    // @see \Drupal\omnipedia_main_page\EventSubscriber\Omnipedia\WikiNodeEditNotFoundToAccessDeniedEventSubscriber::onAccessDeniedToNotFound()
    //   Must be kept in sync with this with the same logic.
    if (
      isset($tab['#access']) &&
      \is_object($tab['#access']) &&
      $tab['#access'] instanceof AccessResultInterface
    ) {
      $tab['#access'] = $tab['#access']->andIf($accessResult);

    // Otherwise, just use our access result.
    } else {
      $tab['#access'] = $accessResult;
    }

  }

}
