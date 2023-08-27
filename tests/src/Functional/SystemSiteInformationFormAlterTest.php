<?php

declare(strict_types=1);

namespace Drupal\Tests\omnipedia_main_page\Functional;

use Drupal\omnipedia_core\Entity\NodeInterface as WikiNodeInterface;
use Drupal\omnipedia_core\Entity\WikiNodeInfo;
use Drupal\omnipedia_core\Service\WikiNodeMainPageInterface;
use Drupal\omnipedia_core\Service\WikiNodeTrackerInterface;
use Drupal\omnipedia_date\Service\DefaultDateInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Tests for the 'system_site_information_settings' form alter.
 *
 * @group omnipedia
 *
 * @group omnipedia_main_page
 */
class SystemSiteInformationFormAlterTest extends BrowserTestBase {

  /**
   * The name of the default date form field.
   */
  protected const DEFAULT_DATE_FIELD = 'default_date';

  /**
   * The Drupal state key where we store the list of dates defined by content.
   */
  protected const DEFINED_DATES_STATE_KEY = 'omnipedia.defined_dates';

  /**
   * The name of our main page title form field.
   */
  protected const MAIN_PAGE_TITLE_FIELD = 'main_page_title';

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
   * The user entity created for this test.
   *
   * @var \Drupal\user\UserInterface
   */
  protected readonly UserInterface $testUser;

  /**
   * Defined dates to generate for the test, in storage format.
   *
   * @var string[]
   */
  protected array $definedDatesData = [
    '2049-09-28',
    '2049-09-29',
    '2049-09-30',
    '2049-10-01',
    '2049-10-02',
  ];

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

    // Set the defined dates to state so that the Omnipedia defined dates
    // service finds those and doesn't attempt to build them from the wiki node
    // tracker, which would return no values as we haven't created any wiki
    // nodes for this test.
    $this->container->get('state')->set(self::DEFINED_DATES_STATE_KEY, [
      'all'       => $this->definedDatesData,
      'published' => $this->definedDatesData,
    ]);

    $this->defaultDate = $this->container->get('omnipedia_date.default_date');

    // Set the initial default date to the last one so that the first iteration
    // of looping through the defined dates actually changes the value.
    $this->defaultDate->set(\end($this->definedDatesData));

    $this->wikiNodeMainPage = $this->container->get(
      'omnipedia.wiki_node_main_page'
    );

    $this->wikiNodeTracker = $this->container->get(
      'omnipedia.wiki_node_tracker'
    );

    /** @var string A consistent main page title to link them as revisions. */
    $mainPageTitle = $this->randomMachineName(8);

    // Create several main page nodes for different dates.
    foreach ($this->definedDatesData as $date) {

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

    // Set the default main page using the default date.
    $this->wikiNodeMainPage->setDefault($this->mainPageNodes[
      $this->defaultDate->get()
    ]);

    $this->testUser = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
    ]);

  }

  /**
   * Test that we've altered the site information form as expected.
   */
  public function testSiteInformationFormIsAltered(): void {

    $this->drupalLogin($this->testUser);

    $this->drupalGet('admin/config/system/site-information');

    // Assert that the core front page field has been removed.
    $this->assertSession()->fieldNotExists('site_frontpage');

    // Assert that our main page title field exists.
    $this->assertSession()->fieldExists(self::MAIN_PAGE_TITLE_FIELD);

    $this->assertSession()->fieldValueEquals(
      self::MAIN_PAGE_TITLE_FIELD, \reset($this->mainPageNodes)->getTitle(),
    );

  }

  /**
   * Test the main page title validation on the site information form.
   */
  public function testSiteInformationFormValidation(): void {

    $this->drupalLogin($this->testUser);

    $this->drupalGet('admin/config/system/site-information');

    // Now submit the form with the title field empty.
    $this->submitForm([
      self::MAIN_PAGE_TITLE_FIELD => '',
    ], 'Save configuration');

    $this->assertSession()->statusMessageExists('error');

    $this->assertSession()->statusMessageContains(
      'The main page title cannot be empty.',
      'error',
    );

    // Now submit the form with the title field set to a random value.
    $this->submitForm([
      self::MAIN_PAGE_TITLE_FIELD => $this->randomMachineName(8),
    ], 'Save configuration');

    $this->assertSession()->statusMessageExists('error');

    $this->assertSession()->statusMessageContains(
      'The main page title doesn\'t match any existing wiki page.',
      'error',
    );

    /** @var \Drupal\omnipedia_core\Entity\NodeInterface */
    $otherWikiNode = $this->drupalCreateNode([
      'title'       => $this->randomMachineName(8),
      'type'        => WikiNodeInfo::TYPE,
      'status'      => WikiNodeInterface::PUBLISHED,
      'field_date'  => \reset($this->definedDatesData),
    ]);

    $this->wikiNodeTracker->trackWikiNode($otherWikiNode);

    // Now submit the form with a wiki node title that exists but doesn't have a
    // date available for the selected default date.
    $this->submitForm([
      self::DEFAULT_DATE_FIELD    => \end($this->definedDatesData),
      self::MAIN_PAGE_TITLE_FIELD => $otherWikiNode->getTitle(),
    ], 'Save configuration');

    $this->assertSession()->statusMessageExists('error');

    $this->assertSession()->statusMessageContains(
      'The main page title exists but that wiki page doesn\'t have a revision available for the specifed default date.',
    );

  }

  /**
   * Test changing the default main page on the site information form.
   */
  public function testSiteInformationFormSubmit(): void {

    $this->drupalLogin($this->testUser);

    $this->drupalGet('admin/config/system/site-information');

    foreach ($this->definedDatesData as $date) {

      /** @var \Drupal\omnipedia_core\Entity\NodeInterface|null */
      $previousMainPage = $this->wikiNodeMainPage->getMainPage('default');

      $this->submitForm([
        self::DEFAULT_DATE_FIELD => $date,
      ], 'Save configuration');

      /** @var \Drupal\omnipedia_core\Entity\NodeInterface|null */
      $newMainPage = $this->wikiNodeMainPage->getMainPage('default');

      // Assert that changing the default date did indeed change the main page
      // node.
      $this->assertNotEquals(
        (int) $previousMainPage->nid->getString(),
        (int) $newMainPage->nid->getString(),
      );

      // Also assert that it's been changed to the expected main page node for
      // the new default date.
      $this->assertEquals(
        (int) $this->mainPageNodes[$this->defaultDate->get()]->nid->getString(),
        (int) $newMainPage->nid->getString(),
      );

    }

  }

}
