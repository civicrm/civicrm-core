<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Angular;

/**
 * Test the Angular base page.
 */
class ManagerTest extends \CiviUnitTestCase {

  /**
   * @var Manager
   */
  protected $angular;

  /**
   * @var \CRM_Core_Resources
   */
  protected $res;

  /**
   * @inheritDoc
   */
  protected function setUp() {
    $this->useTransaction(TRUE);
    parent::setUp();
    $this->createLoggedInUser();
    $this->res = \CRM_Core_Resources::singleton();
    $this->angular = new Manager($this->res);
  }

  /**
   * Modules appear to be well-defined.
   */
  public function testGetModules() {
    $modules = $this->angular->getModules();

    $counts = [
      'js' => 0,
      'css' => 0,
      'partials' => 0,
      'settings' => 0,
    ];

    foreach ($modules as $module) {
      $this->assertTrue(is_array($module));
      $this->assertTrue(is_string($module['ext']));
      if (isset($module['js'])) {
        $this->assertTrue(is_array($module['js']));
        foreach ($module['js'] as $file) {
          $this->assertTrue(file_exists($this->res->getPath($module['ext'], $file)));
          $counts['js']++;
        }
      }
      if (isset($module['css'])) {
        $this->assertTrue(is_array($module['css']));
        foreach ($module['css'] as $file) {
          $this->assertTrue(file_exists($this->res->getPath($module['ext'], $file)));
          $counts['css']++;
        }
      }
      if (isset($module['partials'])) {
        $this->assertTrue(is_array($module['partials']));
        foreach ((array) $module['partials'] as $basedir) {
          $this->assertTrue(is_dir($this->res->getPath($module['ext']) . '/' . $basedir));
          $counts['partials']++;
        }
      }
      if (isset($module['settings'])) {
        $this->assertTrue(is_array($module['settings']));
        foreach ($module['settings'] as $name => $value) {
          $counts['settings']++;
        }
      }
    }

    $this->assertTrue($counts['js'] > 0, 'Expect to find at least one JS file');
    $this->assertTrue($counts['css'] > 0, 'Expect to find at least one CSS file');
    $this->assertTrue($counts['partials'] > 0, 'Expect to find at least one partial HTML file');
    $this->assertTrue($counts['settings'] > 0, 'Expect to find at least one setting');
  }

  /**
   * Get HTML fragments from an example module.
   */
  public function testGetPartials() {
    $partials = $this->angular->getPartials('crmMailing');
    $this->assertRegExp('/ng-form="crmMailingSubform">/', $partials['~/crmMailing/EditMailingCtrl/2step.html']);
    // If crmMailing changes, feel free to use a different example.
  }

  /**
   * Get HTML fragments from an example module. The HTML is modified via hook.
   */
  public function testGetPartials_Hooked() {
    \CRM_Utils_Hook::singleton()->setHook('civicrm_alterAngular', [$this, 'hook_civicrm_alterAngular']);

    $partials = $this->angular->getPartials('crmMailing');
    $this->assertRegExp('/ng-form="crmMailingSubform" cat-stevens="ts\\(\'wild world\'\\)">/', $partials['~/crmMailing/EditMailingCtrl/2step.html']);
    // If crmMailing changes, feel free to use a different example.
  }

  public function testGetJs_Asset() {
    \CRM_Utils_Hook::singleton()->setHook('civicrm_angularModules', [$this, 'hook_civicrm_angularModules_fooBar']);

    $paths = $this->angular->getResources(['fooBar'], 'js', 'path');
    $this->assertRegExp('/visual-bundle.[a-z0-9]+.js/', $paths[0]);
    $this->assertRegExp('/crossfilter/', file_get_contents($paths[0]));

    $this->assertRegExp('/Common.js/', $paths[1]);
    $this->assertRegExp('/console/', file_get_contents($paths[1]));
  }

  /**
   * Get a translatable string from an example module.
   */
  public function testGetStrings() {
    $strings = $this->angular->getStrings('crmMailing');
    $this->assertTrue(in_array('Save Draft', $strings));
    $this->assertFalse(in_array('wild world', $strings));
    // If crmMailing changes, feel free to use a different example.
  }

  /**
   * Get a translatable string from an example module. The HTML is modified via hook.
   */
  public function testGetStrings_Hooked() {
    \CRM_Utils_Hook::singleton()->setHook('civicrm_alterAngular', [$this, 'hook_civicrm_alterAngular']);

    $strings = $this->angular->getStrings('crmMailing');
    $this->assertTrue(in_array('wild world', $strings));
    // If crmMailing changes, feel free to use a different example.
  }

  /**
   * Get the list of dependencies for an Angular module.
   */
  public function testGetRequires() {
    $requires = $this->angular->getResources(['crmMailing'], 'requires', 'requires');
    $this->assertTrue(in_array('ngRoute', $requires['crmMailing']));
    $this->assertFalse(in_array('crmCatStevens', $requires['crmMailing']));
    // If crmMailing changes, feel free to use a different example.
  }

  /**
   * Get the list of dependencies for an Angular module. It can be modified via hook.
   */
  public function testGetRequires_Hooked() {
    \CRM_Utils_Hook::singleton()->setHook('civicrm_alterAngular', [$this, 'hook_civicrm_alterAngular']);

    $requires = $this->angular->getResources(['crmMailing'], 'requires', 'requires');
    $this->assertTrue(in_array('ngRoute', $requires['crmMailing']));
    $this->assertTrue(in_array('crmCatStevens', $requires['crmMailing']));
    // If crmMailing changes, feel free to use a different example.
  }

  /**
   * Get the full, recursive list of dependencies for a set of Angular modules.
   */
  public function testResolveDeps() {
    // If crmMailing changes, feel free to use a different example.
    $expected = [
      'angularFileUpload',
      'crmAttachment',
      'crmAutosave',
      'crmCxn',
      'crmMailing',
      'crmResource',
      'crmUtil',
      'crmUi',
      'dialogService',
      'ngRoute',
      'ngSanitize',
      'ui.utils',
    ];
    $input = ['crmMailing', 'crmCxn'];
    $actual = $this->angular->resolveDependencies($input);
    sort($expected);
    sort($actual);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Example hook. Modifies `2step.html` by adding the attribute
   * `cat-stevens="ts('wild world')"`.
   *
   * @param \Civi\Angular\Manager $angular
   * @see \CRM_Utils_Hook::alterAngular
   */
  public function hook_civicrm_alterAngular($angular) {
    $angular->add(ChangeSet::create('cat-stevens')
      ->requires('crmMailing', 'crmCatStevens')
      ->alterHtml('~/crmMailing/EditMailingCtrl/2step.html', function(\phpQueryObject $doc) {
        $doc->find('[ng-form="crmMailingSubform"]')->attr('cat-stevens', 'ts(\'wild world\')');
      })
    );
  }

  public function hook_civicrm_angularModules_fooBar(&$angularModules) {
    $angularModules['fooBar'] = [
      'ext' => 'civicrm',
      'js' => [
        'assetBuilder://visual-bundle.js',
        'ext://civicrm/js/Common.js',
      ],
    ];
  }

}
