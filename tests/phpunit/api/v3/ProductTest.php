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
 * Class api_v3_ProductTest
 * @group headless
 */
class api_v3_ProductTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_params;

  protected $_entity = 'Product';

  public function setUp() {
    parent::setUp();
    $this->useTransaction();
    $this->_params = [
      'name' => 'my product',
    ];
  }

  public function testGetFields() {
    $fields = $this->callAPISuccess($this->_entity, 'getfields', ['action' => 'create']);
    $this->assertArrayHasKey('period_type', $fields['values']);
  }

  public function testGetOptions() {
    $options = $this->callAPISuccess($this->_entity, 'getoptions', ['field' => 'period_type']);
    $this->assertArrayHasKey('rolling', $options['values']);
  }

}
