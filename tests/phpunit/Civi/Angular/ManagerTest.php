<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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

    $counts = array(
      'js' => 0,
      'css' => 0,
      'partials' => 0,
      'settings' => 0,
    );

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
    $this->assertRegExp('/\<form.*name="crmMailing"/', $partials['~/crmMailing/EditMailingCtrl/2step.html']);
    // If crmMailing changes, feel free to use a different example.
  }

  /**
   * Get a translatable string from an example module.
   */
  public function testGetStrings() {
    $strings = $this->angular->getStrings('crmMailing');
    $this->assertTrue(in_array('Save Draft', $strings));
    // If crmMailing changes, feel free to use a different example.
  }

}
