<?php

declare(strict_types=1);

namespace Drupal\Tests\omnipedia_main_page\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for installing and uninstalling this module.
 *
 * @group omnipedia
 *
 * @group omnipedia_main_page
 */
class InstallUninstallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime', 'field', 'filter', 'menu_ui', 'node', 'omnipedia_core',
    'system', 'taxonomy', 'text', 'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $this->installEntitySchema('filter_format');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');

    $this->installSchema('user', 'users_data');

    $this->installConfig([
      'field', 'filter', 'node', 'omnipedia_core', 'system',
    ]);

  }

  public function testInstallUninstall(): void {

    /** @var \Drupal\Core\Extension\ModuleInstallerInterface The Drupal module installer service. */
    $moduleInstaller = $this->container->get('module_installer');

    $moduleInstaller->install(['omnipedia_main_page'], false);

    $this->assertEquals(
      Url::fromRoute('omnipedia_main_page.main_page')->toString(),
      $this->container->get('config.factory')->get('system.site')->get(
        'page.front',
      ),
    );

    $moduleInstaller->uninstall(['omnipedia_main_page'], false);

    $this->assertEquals(
      '',
      $this->container->get('config.factory')->get('system.site')->get(
        'page.front',
      ),
    );

  }

}
