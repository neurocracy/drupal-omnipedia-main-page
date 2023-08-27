<?php

declare(strict_types=1);

namespace Drupal\Tests\omnipedia_main_page\Functional;

use Drupal\omnipedia_core\Entity\NodeInterface as WikiNodeInterface;
use Drupal\omnipedia_core\Entity\WikiNodeInfo;
use Drupal\omnipedia_core\Service\WikiNodeMainPageInterface;
use Drupal\omnipedia_core\Service\WikiNodeTrackerInterface;
use Drupal\omnipedia_date\Service\DefaultDateInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the main page controller.
 *
 * @group omnipedia
 *
 * @group omnipedia_main_page
 */
class MainPageControllerTest extends BrowserTestBase {

  /**
   * The Omnipedia default date service.
   *
   * @var \Drupal\omnipedia_date\Service\DefaultDateInterface
   */
  protected readonly DefaultDateInterface $defaultDate;

  /**
   * The Omnipedia wiki node main page service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeMainPageInterface
   */
  protected readonly WikiNodeMainPageInterface $wikiNodeMainPage;

  /**
   * The Omnipedia wiki node tracker service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeTrackerInterface
   */
  protected readonly WikiNodeTrackerInterface $wikiNodeTracker;

  /**
   * Main page nodes created for the test.
   *
   * @var \Drupal\omnipedia_core\Entity\NodeInterface[]
   */
  protected array $mainPageNodes = [];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['omnipedia_main_page'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $this->defaultDate = $this->container->get('omnipedia_date.default_date');

    $this->wikiNodeMainPage = $this->container->get(
      'omnipedia.wiki_node_main_page'
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

      /** @var \Drupal\omnipedia_core\Entity\NodeInterface */
      $this->mainPageNodes[$date] = $this->drupalCreateNode([
        'title'       => $mainPageTitle,
        'type'        => WikiNodeInfo::TYPE,
        'status'      => WikiNodeInterface::PUBLISHED,
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
   *
   * @dataProvider datesDataProvider
   */
  public function testRedirect(string $date): void {

    // Set the default date.
    $this->defaultDate->set($date);

    // Set the default main page using the provided default date.
    $this->wikiNodeMainPage->setDefault($this->mainPageNodes[$date]);

    // Request the base URL which should redirect to the node's canonical URL.
    $this->drupalGet('');

    $this->assertSession()->addressEquals($this->mainPageNodes[$date]->toUrl());

  }

  /**
   * Test that visiting the base URL doesn't redirect to unpublished main pages.
   *
   * @dataProvider datesDataProvider
   */
  public function testRedirectAccess(string $date): void {

    // Set the default date.
    $this->defaultDate->set($date);

    // Set the main page to unpublished which is not accessible to anonymous
    // users with the default permissions for that role.
    $this->mainPageNodes[$date]->setUnpublished()->save();

    // Set the default main page using the provided default date.
    $this->wikiNodeMainPage->setDefault($this->mainPageNodes[$date]);

    // Request the base URL.
    $this->drupalGet('');

    // This should result in a 403 access denied.
    $this->assertSession()->statusCodeEquals(403);

    // The redirect should not have occurred as the user doesn't have access.
    $this->assertSession()->addressEquals('/');

  }

}
