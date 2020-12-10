<?php
/**
 *  File for the CRM_Contact_Form_Search_Custom_GroupTest class
 *
 *  (PHP 5)
 *
 * @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 * @copyright Copyright CiviCRM LLC (C) 2009
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 * @package CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

/**
 *  Include parent class definition
 */


/**
 *  Include class under test
 */

/**
 *  Include form definitions
 */

/**
 *  Include DAO to do queries
 */

/**
 *  Include dataProvider for tests
 */

/**
 *  Test contact custom search functions
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Form_Search_Custom_PriceSetTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
  }

  public function testRunSearch() {
    $order = $this->callAPISuccess('Order', 'create', $this->getParticipantOrderParams());
    $this->callAPISuccess('Payment', 'create', [
      'order_id' => $order['id'],
      'total_amount' => 50,
    ]);
    $this->validateAllPayments();
    $formValues = ['event_id' => $this->_eventId];
    $form = new CRM_Contact_Form_Search_Custom_PriceSet($formValues);
    $sql = $form->all();
    // Assert that we have created a standard temp table
    $this->assertContains('civicrm_tmp_e_priceset', $sql);
    // Check that the temp table has been populated.
    $result = CRM_Core_DAO::executeQuery($sql)->fetchAll();
    $this->assertTrue(!empty($result));
  }

}
