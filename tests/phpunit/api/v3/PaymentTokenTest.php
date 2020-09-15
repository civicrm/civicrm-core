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

/**
 * Class api_v3_PaymentTokenTest
 * @group headless
 */
class api_v3_PaymentTokenTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $params;
  protected $id;

  public $DBResetRequired = FALSE;

  public function setUp() {
    $this->_apiversion = 3;
    $this->useTransaction(TRUE);
    parent::setUp();
    $contactID = $this->individualCreate();
    $this->params = [
      'token' => "fancy-token-xxxx",
      'contact_id' => $contactID,
      'created_id' => $contactID,
      'payment_processor_id' => $this->processorCreate(),
    ];
  }

  public function testCreatePaymentToken() {
    $description = "Create a payment token - Note use of relative dates here:
      @link http://www.php.net/manual/en/datetime.formats.relative.php.";
    $result = $this->callAPIAndDocument('payment_token', 'create', $this->params, __FUNCTION__, __FILE__, $description);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck(array_merge($this->params, [$this->params]), $result['id'], 'payment_token', TRUE);
  }

  public function testGetPaymentToken() {
    $result = $this->callAPISuccess('payment_token', 'create', $this->params);
    $result = $this->callAPIAndDocument('payment_token', 'get', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  public function testDeletePaymentToken() {
    $this->callAPISuccess('payment_token', 'create', $this->params);
    $entity = $this->callAPISuccess('payment_token', 'get', ($this->params));
    $delete = ['id' => $entity['id']];
    $result = $this->callAPIAndDocument('payment_token', 'delete', $delete, __FUNCTION__, __FILE__);

    $checkDeleted = $this->callAPISuccess('payment_token', 'get', []);
    $this->assertEquals(0, $checkDeleted['count']);
  }

}
