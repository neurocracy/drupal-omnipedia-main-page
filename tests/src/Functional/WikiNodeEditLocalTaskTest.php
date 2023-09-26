<?php

declare(strict_types=1);

namespace Drupal\Tests\omnipedia_main_page\Functional;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\omnipedia_core\Entity\WikiNodeInfo;
use Drupal\omnipedia_date\Service\DefaultDateInterface;
use Drupal\omnipedia_main_page\Service\MainPageDefaultInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests for wiki node 'Edit' local task visibility.
 *
 * @group omnipedia
 *
 * @group omnipedia_main_page
 *
 * @see \Drupal\Tests\omnipedia_core\Functional\WikiNodeEditLocalTaskTest
 *   Tests the general functionality that doesn't involve main pages
 *   specifically.
 */
class WikiNodeEditLocalTaskTest extends BrowserTestBase {

  /**
   * The Omnipedia default date service.
   *
   * @var \Drupal\omnipedia_date\Service\DefaultDateInterface
   */
  protected readonly DefaultDateInterface $defaultDate;

  /**
   * The Omnipedia default main page service.
   *
   * @var \Drupal\omnipedia_main_page\Service\MainPageDefaultInterface
   */
  protected readonly MainPageDefaultInterface $mainPageDefault;

  /**
   * The configured main page wiki node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected readonly NodeInterface $mainPageNode;

  /**
   * The local tasks HTML 'id' attribute slug.
   */
  protected const LOCAL_TASKS_BLOCK_ID = 'local-tasks-block';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block', 'field', 'node', 'omnipedia_access', 'omnipedia_core',
    'omnipedia_date', 'omnipedia_main_page', 'system', 'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $this->defaultDate = $this->container->get('omnipedia_date.default_date');

    $this->mainPageDefault = $this->container->get(
      'omnipedia_main_page.default',
    );

    /** @var \Drupal\node\NodeInterface */
    $this->mainPageNode = $this->drupalCreateNode([
      'title'       => $this->randomMachineName(8),
      'type'        => WikiNodeInfo::TYPE,
      'status'      => NodeInterface::PUBLISHED,
      'field_date'  => '2049-10-01',
    ]);

    // Must be set to avoid this service throwing an error. Should be the same
    // as the default main page node's date.
    $this->defaultDate->set('2049-10-01');

    $this->mainPageDefault->set($this->mainPageNode);

    $this->drupalPlaceBlock('local_tasks_block', [
      'region' => 'content', 'id' => self::LOCAL_TASKS_BLOCK_ID,
    ]);

  }

  /**
   * Assert that a local task with the provided Url is present on the page.
   *
   * @param \Drupal\Core\Url $url
   *
   * @todo Rework this into a reusable trait.
   */
  protected function assertHasLocalTask(Url $url): void {

    // @see \Drupal\Core\Utility\LinkGenerator::generate()
    //   'data-drupal-link-system-path' attributes are generated here using
    //   Url::getInternalPath() so we use the same method to build our selector.
    $this->assertSession()->elementExists('css',
      '#block-' . self::LOCAL_TASKS_BLOCK_ID . ' ' .
      'a[data-drupal-link-system-path="' . $url->getInternalPath() . '"]'
    );

  }

  /**
   * Assert that a local task with the provided Url is not present on the page.
   *
   * @param \Drupal\Core\Url $url
   *
   * @todo Rework this into a reusable trait.
   */
  protected function assertNotHasLocalTask(Url $url): void {

    // @see \Drupal\Core\Utility\LinkGenerator::generate()
    //   'data-drupal-link-system-path' attributes are generated here using
    //   Url::getInternalPath() so we use the same method to build our selector.
    $this->assertSession()->elementNotExists('css',
      '#block-' . self::LOCAL_TASKS_BLOCK_ID . ' ' .
      'a[data-drupal-link-system-path="' . $url->getInternalPath() . '"]'
    );

  }

  /**
   * Test that 'Edit' local tasks only appear on main pages for real editors.
   *
   * I.e. it's hidden for users without real edit access but shown as expected
   * for users who actually have access to edit the page.
   */
  public function testMainPageNoEditLocalTask(): void {

    $this->drupalGet($this->mainPageNode->toUrl());

    $this->assertNotHasLocalTask($this->mainPageNode->toUrl('edit-form'));

    $user = $this->drupalCreateUser([
      'access content',
      'edit any ' . WikiNodeInfo::TYPE . ' content',
    ]);

    $this->drupalLogin($user);

    $this->drupalGet($this->mainPageNode->toUrl());

    $this->assertHasLocalTask($this->mainPageNode->toUrl('edit-form'));

  }

  /**
   * Test that the main page node edit route shows not found.
   */
  public function testMainPageEditRouteNotFound(): void {

    $this->drupalGet($this->mainPageNode->toUrl('edit-form'));

    $this->assertSession()->statusCodeEquals(404);

  }

}
