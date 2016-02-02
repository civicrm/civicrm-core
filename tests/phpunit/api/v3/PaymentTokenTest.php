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

/**
 * Class api_v3_PaymentTokenTest
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
    $this->params = array(
      'token' => "fancy-token-xxxx",
      'contact_id' => $contactID,
      'created_id' => $contactID,
      'payment_processor_id' => $this->processorCreate(),
    );
  }

  public function testCreatePaymentToken() {
    $description = "Create a payment token - Note use of relative dates here:
      @link http://www.php.net/manual/en/datetime.formats.relative.php.";
    $result = $this->callAPIAndDocument('payment_token', 'create', $this->params, __FUNCTION__, __FILE__, $description);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck(array_merge($this->params, array($this->params)), $result['id'], 'payment_token', TRUE);
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
    $delete = array('id' => $entity['id']);
    $result = $this->callAPIAndDocument('payment_token', 'delete', $delete, __FUNCTION__, __FILE__);

    $checkDeleted = $this->callAPISuccess('payment_token', 'get', array());
    $this->assertEquals(0, $checkDeleted['count']);
  }

}
