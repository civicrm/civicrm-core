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
  protected $params;
  protected $id;

  /**
   * Setup for class.
   *
   * @throws \CRM_Core_Exception
   */
  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
    $contactID = $this->individualCreate();
    $this->params = [
      'token' => "fancy-token-xxxx",
      'contact_id' => $contactID,
      'created_id' => $contactID,
      'payment_processor_id' => $this->processorCreate(),
    ];
  }

  /**
   * Test create token.
   *
   * @dataProvider versionThreeAndFour
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreatePaymentToken(): void {
    $result = $this->callAPISuccess('payment_token', 'create', $this->params);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck($this->params, $result['id'], 'payment_token', TRUE);
  }

  /**
   * Get token test.
   *
   * @dataProvider versionThreeAndFour
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetPaymentToken(): void {
    $this->callAPISuccess('payment_token', 'create', $this->params);
    $result = $this->callAPISuccess('payment_token', 'get', $this->params);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  /**
   * Delete token test.
   *
   * @dataProvider versionThreeAndFour
   *
   * @throws \CRM_Core_Exception
   */
  public function testDeletePaymentToken(): void {
    $this->callAPISuccess('payment_token', 'create', $this->params);
    $entity = $this->callAPISuccess('payment_token', 'get', ($this->params));
    $delete = ['id' => $entity['id']];
    $this->callAPISuccess('payment_token', 'delete', $delete);

    $checkDeleted = $this->callAPISuccess('payment_token', 'get', []);
    $this->assertEquals(0, $checkDeleted['count']);
  }

}
