<?php
/**
 *  File for the CRM_Contact_Form_Search_Custom_PriceSetTest class
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

use Civi\Test;
use Civi\Test\Api3TestTrait;
use Civi\Test\ContactTestTrait;
use Civi\Test\EventTestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 *  Test contact custom search functions
 *
 * @group headless
 */
class CRM_Contact_Form_Search_Custom_PriceSetTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use EventTestTrait;
  use Api3TestTrait;
  use ContactTestTrait;

  /**
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and
   * sqlFile(). See:
   * https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   */
  public function setUpHeadless(): Test\CiviEnvBuilder {
    return Test::headless()
      ->install(['legacycustomsearches'])
      ->apply();
  }

  /**
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function testRunSearch(): void {
    $this->eventCreatePaid();
    $contactID = $this->individualCreate();
    $this->callAPISuccess('Order', 'create', [
      'total_amount' => 100,
      'currency' => 'USD',
      'contact_id' => $contactID,
      'financial_type_id' => 4,
      'line_items' => [
        [
          'line_item' => [
            [
              'price_field_value_id' => $this->ids['PriceFieldValue']['PaidEvent_student'],
              'price_field_id' => $this->ids['PriceField']['PaidEvent'],
              'qty' => 1,
              'line_total' => 100,
              'entity_table' => 'civicrm_participant',
            ],
          ],
          'params' => [
            'financial_type_id' => 4,
            'event_id' => $this->getEventID(),
            'role_id' => 1,
            'status_id' => 14,
            'fee_currency' => 'USD',
            'contact_id' => $contactID ,
          ],
        ],
      ],
    ]);
    $formValues = ['event_id' => $this->getEventID()];
    $form = new CRM_Contact_Form_Search_Custom_PriceSet($formValues);
    $sql = $form->all();
    // Assert that we have created a standard temp table
    $this->assertStringContainsString('civicrm_tmp_e_priceset', $sql);
    // Check that the temp table has been populated.
    $result = CRM_Core_DAO::executeQuery($sql)->fetchAll();
    $this->assertNotEmpty($result);
  }

}
