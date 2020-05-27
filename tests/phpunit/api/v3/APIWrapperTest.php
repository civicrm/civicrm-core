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

require_once 'api/Wrapper.php';

/**
 * Test class for API functions
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_APIWrapperTest extends CiviUnitTestCase {
  public $DBResetRequired = FALSE;


  protected $_apiversion = 3;

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
    CRM_Utils_Hook_UnitTests::singleton()->setHook('civicrm_apiWrappers', [$this, 'onApiWrappers']);
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown() {
    parent::tearDown();
  }

  /**
   * @param $apiWrappers
   * @param $apiRequest
   */
  public function onApiWrappers(&$apiWrappers, $apiRequest) {
    $this->assertTrue(is_string($apiRequest['entity']) && !empty($apiRequest['entity']));
    $this->assertTrue(is_string($apiRequest['action']) && !empty($apiRequest['action']));
    $this->assertTrue(is_array($apiRequest['params']) && !empty($apiRequest['params']));

    $apiWrappers[] = new api_v3_APIWrapperTest_Impl();
  }

  public function testWrapperHook() {
    // Note: this API call would fail due to missing contact_type, but
    // the wrapper intervenes (fromApiInput)
    // Note: The output would define "display_name", but the wrapper
    // intervenes (toApiOutput) and replaces with "display_name_munged".
    $result = $this->callAPISuccess('contact', 'create', [
      'contact_type' => 'Invalid',
      'first_name' => 'First',
      'last_name' => 'Last',
    ]);
    $this->assertEquals('First', $result['values'][$result['id']]['first_name']);
    $this->assertEquals('MUNGE! First Last', $result['values'][$result['id']]['display_name_munged']);
  }

}

/**
 * Class api_v3_APIWrapperTest_Impl
 */
class api_v3_APIWrapperTest_Impl implements API_Wrapper {

  /**
   * @inheritDoc
   */
  public function fromApiInput($apiRequest) {
    if ($apiRequest['entity'] == 'Contact' && $apiRequest['action'] == 'create') {
      if ('Invalid' == CRM_Utils_Array::value('contact_type', $apiRequest['params'])) {
        $apiRequest['params']['contact_type'] = 'Individual';
      }
    }
    return $apiRequest;
  }

  /**
   * @inheritDoc
   */
  public function toApiOutput($apiRequest, $result) {
    if ($apiRequest['entity'] == 'Contact' && $apiRequest['action'] == 'create') {
      if (isset($result['id'], $result['values'][$result['id']]['display_name'])) {
        $result['values'][$result['id']]['display_name_munged'] = 'MUNGE! ' . $result['values'][$result['id']]['display_name'];
        unset($result['values'][$result['id']]['display_name']);
      }
    }
    return $result;
  }

}
