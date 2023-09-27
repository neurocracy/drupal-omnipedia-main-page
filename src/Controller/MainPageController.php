<?php

declare(strict_types=1);

namespace Drupal\omnipedia_main_page\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\omnipedia_date\Service\CurrentDateInterface;
use Drupal\omnipedia_main_page\Service\MainPageCacheInterface;
use Drupal\omnipedia_main_page\Service\MainPageResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Omnipedia main page route controller.
 */
class MainPageController implements ContainerInjectionInterface {

  /**
   * Constructs this controller; saves dependencies.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user proxy service.
   *
   * @param \Drupal\omnipedia_date\Service\CurrentDateInterface $currentDate
   *   The Omnipedia current date service.
   *
   * @param \Drupal\omnipedia_main_page\Service\MainPageCacheInterface $mainPageCache
   *   The Omnipedia main page cache service.
   *
   * @param \Drupal\omnipedia_main_page\Service\MainPageResolverInterface $mainPageResolver
   *   The Omnipedia main page resolver service.
   */
  public function __construct(
    protected readonly AccountProxyInterface      $currentUser,
    protected readonly CurrentDateInterface       $currentDate,
    protected readonly MainPageCacheInterface     $mainPageCache,
    protected readonly MainPageResolverInterface  $mainPageResolver,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('omnipedia_date.current_date'),
      $container->get('omnipedia_main_page.cache'),
      $container->get('omnipedia_main_page.resolver'),
    );
  }

  /**
   * Get the main page wiki node to redirect to.
   *
   * @return \Drupal\node\NodeInterface
   *   The main page wiki node for the current date, or for the default date if
   *   a current date is not set for the current user.
   *
   * @see \Drupal\omnipedia_main_page\Service\MainPageResolverInterface::get()
   *
   * @see \Drupal\omnipedia_date\Service\CurrentDateInterface::get()
   */
  protected function getMainPage(): ?NodeInterface {
    return $this->mainPageResolver->get($this->currentDate->get());
  }

  /**
   * Get the cacheable GeneratedUrl object for the main page to link to.
   *
   * @return \Drupal\Core\GeneratedUrl
   *
   * @see \Drupal\Core\Render\BubbleableMetadata
   *   GeneratedUrl extends this so it's a full cacheable metadata object.
   *
   * @todo Move these cache contexts and tags to a central, shared location.
   */
  protected function getCacheableGeneratedUrl(): GeneratedUrl {

    /** @var \Drupal\Core\GeneratedUrl */
    $generatedUrl = $this->getMainPage()->toUrl()->toString(true);

    $generatedUrl->addCacheContexts([
      'omnipedia_dates',
      'user.permissions',
      'user.node_grants:view',
    ])->addCacheTags($this->mainPageCache->getAllCacheTags());

    return $generatedUrl;

  }

  /**
   * Checks access for the request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(
    AccountInterface $account,
  ): AccessResultInterface {

    $mainPage = $this->getMainPage();

    return AccessResult::allowedIf(
      \is_object($mainPage) &&
      $mainPage->access('view', $account)
    // We need to add the GeneratedUrl object as a cacheable dependency so that
    // the access result is cached with the necessary cache contexts and tags.
    )->addCacheableDependency($this->getCacheableGeneratedUrl());

  }

  /**
   * Callback for the route.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   A trusted redirect response object.
   */
  public function view(): TrustedRedirectResponse {

    /** @var \Drupal\Core\GeneratedUrl */
    $generatedUrl = $this->getCacheableGeneratedUrl();

    // We have to add the GeneratedUrl object as a cacheable dependency to the
    // redirect response so that Drupal varies the redirect according to our
    // cache contexts. Without this, we'd be redirected to the main page with
    // the first cached date on all subsequent redirects regardless of the
    // current date at the time of the redirect.
    return (new TrustedRedirectResponse(
      $generatedUrl->getGeneratedUrl(), 302,
    ))->addCacheableDependency($generatedUrl);

  }

}
