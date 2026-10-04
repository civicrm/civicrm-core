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

use Civi\Api4\Navigation;
use Civi\Test\FormTrait;

/**
 * Tests for CRM_Admin_Form_Navigation.
 *
 * @group headless
 */
class CRM_Admin_Form_NavigationTest extends CiviUnitTestCase {
  use FormTrait;

  private $originalRequest;

  public function setUp(): void {
    parent::setUp();
    $this->originalRequest = [
      'method' => $_SERVER['REQUEST_METHOD'] ?? NULL,
      'httpx' => $_SERVER['HTTP_X_REQUESTED_WITH'] ?? NULL,
    ];
  }

  public function tearDown(): void {
    $_SERVER['REQUEST_METHOD'] = $this->originalRequest['method'];
    $_SERVER['HTTP_X_REQUESTED_WITH'] = $this->originalRequest['httpx'];
    parent::tearDown();
  }

  /**
   * Test submitting CRM_Admin_Form_Navigation with target field and verifying AJAX output.
   */
  public function testSubmitNavigationFormWithTarget(): void {
    $this->createLoggedInUser();

    $submittedValues = [
      'label' => 'Test link in new tab',
      'url' => 'https://example.org/docs',
      'target' => '_blank',
      'is_active' => 1,
    ];

    $this->getTestForm('CRM_Admin_Form_Navigation', $submittedValues)->processForm();

    // Verify record in database via APIv4
    $nav = Navigation::get(FALSE)
      ->addWhere('label', '=', 'Test link in new tab')
      ->execute()
      ->first();

    $this->assertNotEmpty($nav);
    $this->assertEquals('https://example.org/docs', $nav['url']);
    $this->assertEquals('_blank', $nav['target']);

    // Verify AJAX output in CRM_Admin_Page_AJAX::navMenu()
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

    ob_start();
    try {
      CRM_Admin_Page_AJAX::navMenu();
    }
    catch (\CRM_Core_Exception_PrematureExitException $e) {
    }
    $rawOutput = ob_get_clean();

    $data = json_decode($rawOutput, TRUE);
    $this->assertIsArray($data);
    $this->assertNotEmpty($data['menu']);

    $menuItem = CRM_Utils_Array::findAll($data['menu'], ['label' => 'Test link in new tab'])[0];
    $this->assertEquals('https://example.org/docs', $menuItem['url']);
    $this->assertEquals('_blank', $menuItem['target']);
  }

}
