<?php

declare(strict_types=1);

namespace Drupal\Tests\omnipedia_main_page\Functional;

use Drupal\Core\Config\PreExistingConfigException;
use Drupal\node\NodeInterface;
use Drupal\omnipedia_core\Entity\WikiNodeInfo;
use Drupal\omnipedia_core\Service\WikiNodeTrackerInterface;
use Drupal\omnipedia_date\Service\DefaultDateInterface;
use Drupal\omnipedia_main_page\Service\MainPageDefaultInterface;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for the main page controller.
 */
#[Group('omnipedia')]
#[Group('omnipedia_main_page')]
#[RunTestsInSeparateProcesses]
class MainPageControllerTest extends BrowserTestBase {

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
   * The Omnipedia wiki node tracker service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeTrackerInterface
   */
  protected readonly WikiNodeTrackerInterface $wikiNodeTracker;

  /**
   * Main page nodes created for the test.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected array $mainPageNodes = [];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    // This needs to catch \Drupal\Core\Config\PreExistingConfigException if
    // thrown. Drupal >= 11.3 will not have field.storage.node.body, so we need
    // to attempt to install the module below which provides it, but doing will
    // result in PreExistingConfigException being thrown due to the field
    // storage already existing in Drupal < 11.3.
    //
    // @see https://gitlab.com/neurocracy/omnipedia/omnipedia/-/work_items/77
    try {

      $this->container->get('module_installer')->install([
        'omnipedia_core_wiki_node_test_dependencies',
      ]);

    } catch (PreExistingConfigException $exception) {}

    // We're installing this here rather than in $modules to work around
    // field.storage.node.body not being found, giving the test module above a
    // chance to install it before omnipedia_core is installed.
    //
    // @see https://gitlab.com/neurocracy/omnipedia/omnipedia/-/work_items/77
    $this->container->get('module_installer')->install(['omnipedia_main_page']);

    // Seems to be necessary to pick up the omnipedia_date and
    // omnipedia_main_page services below.
    $this->rebuildContainer();

    $this->defaultDate = $this->container->get('omnipedia_date.default_date');

    $this->mainPageDefault = $this->container->get(
      'omnipedia_main_page.default',
    );

    $this->wikiNodeTracker = $this->container->get(
      'omnipedia.wiki_node_tracker'
    );

    /** @var string A consistent main page title to link them as revisions. */
    $mainPageTitle = $this->randomMachineName(8);

    // Create several main page nodes for different dates.
    foreach ($this->datesDataProvider() as $providerData) {

      /** @var string A date in the storage format */
      $date = $providerData[0];

      /** @var \Drupal\node\NodeInterface */
      $this->mainPageNodes[$date] = $this->drupalCreateNode([
        'title'       => $mainPageTitle,
        'type'        => WikiNodeInfo::TYPE,
        'status'      => NodeInterface::PUBLISHED,
        'field_date'  => $date,
      ]);

      // Required so the main page service has data to pull in to correctly
      // check if the route is a main page.
      $this->wikiNodeTracker->trackWikiNode($this->mainPageNodes[$date]);

    }

  }

  /**
   * Data provider for self::testRedirect().
   *
   * @return array
   */
  public static function datesDataProvider(): array {

    return [
      ['2049-09-28'],
      ['2049-09-29'],
      ['2049-09-30'],
      ['2049-10-01'],
      ['2049-10-02'],
    ];

  }

  /**
   * Test that visiting the base URL redirects to the expected main page node.
   */
  #[DataProvider('datesDataProvider')]
  public function testRedirect(string $date): void {

    // Set the default date.
    $this->defaultDate->set($date);

    // Set the default main page using the provided default date.
    $this->mainPageDefault->set($this->mainPageNodes[$date]);

    // Request the base URL which should redirect to the node's canonical URL.
    $this->drupalGet('');

    $this->assertSession()->addressEquals($this->mainPageNodes[$date]->toUrl());

  }

  /**
   * Test that visiting the base URL doesn't redirect to unpublished main pages.
   */
  #[DataProvider('datesDataProvider')]
  public function testRedirectAccess(string $date): void {

    // Set the default date.
    $this->defaultDate->set($date);

    // Set the main page to unpublished which is not accessible to anonymous
    // users with the default permissions for that role.
    $this->mainPageNodes[$date]->setUnpublished()->save();

    // Set the default main page using the provided default date.
    $this->mainPageDefault->set($this->mainPageNodes[$date]);

    // Request the base URL.
    $this->drupalGet('');

    // This should result in a 403 access denied.
    $this->assertSession()->statusCodeEquals(403);

    // The redirect should not have occurred as the user doesn't have access.
    $this->assertSession()->addressEquals('/');

  }

}
