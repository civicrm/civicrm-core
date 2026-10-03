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

namespace Civi\Standalone;

/**
 * @group headless
 */
class ErrorHandlerTest extends \CiviUnitTestCase {

  private bool $debug;

  public function setUp(): void {
    parent::setUp();
    (new \ReflectionProperty(ErrorHandler::class, 'messages'))->setValue(NULL, []);
    $this->debug = (bool) \CRM_Core_Config::singleton()->debug;
    \CRM_Core_Config::singleton()->debug = TRUE;
  }

  public function tearDown(): void {
    \CRM_Core_Config::singleton()->debug = $this->debug;
    (new \ReflectionProperty(ErrorHandler::class, 'messages'))->setValue(NULL, []);
    parent::tearDown();
  }

  public function testErrorSilencedWithAtIsNotRendered(): void {
    ErrorHandler::setHandler();
    try {
      @trigger_error('silenced warning', E_USER_WARNING);
      trigger_error('reported warning', E_USER_WARNING);
    }
    finally {
      restore_error_handler();
    }

    $page = '<!-- STANDALONE ERRORS PLACEHOLDER -->';
    ErrorHandler::renderErrors($page);
    $this->assertStringContainsString('reported warning', $page);
    $this->assertStringNotContainsString('silenced warning', $page);
  }

}
