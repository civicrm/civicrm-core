<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Test class for CRM_Contact_BAO_Group BAO
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Form_Search_SearchContactTest extends CiviUnitTestCase {

  /**
   * Test contact sub type search.
   */
  public function testContactSubtype() {
    foreach (['Contact_sub_type', 'Contact2__sub__type'] as $contactSubType) {
      $subType = $this->callAPISuccess('ContactType', 'create', [
        'name' => $contactSubType,
        'label' => $contactSubType,
        'is_active' => 1,
        'parent_id' => "Individual",
      ]);
      // Contact Type api munge name in create mode
      // Therefore updating the name in update mode
      $this->callAPISuccess('ContactType', 'create', [
        'name' => $contactSubType,
        'id' => $subType['id'],
      ]);
    }
    $this->searchContacts('Contact_sub_type');
    $this->searchContacts('Contact2__sub__type');
  }

  protected function searchContacts($contactSubType) {
    // create contact
    $params = [
      'first_name' => 'Peter' . substr(sha1(rand()), 0, 4),
      'last_name' => 'Lastname',
      'contact_type' => 'Individual',
      'contact_sub_type' => $contactSubType,
    ];
    $contacts = $this->callAPISuccess('Contact', 'create', $params);
    $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements(TRUE);
    foreach ($contactTypes as $contactType => $ignore) {
      if (strpos($contactType, $contactSubType) !== FALSE) {
        $formValues = [
          'contact_type' => $contactType,
        ];
        break;
      }
    }
    CRM_Contact_BAO_Query::convertFormValues($formValues);
    $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($formValues));
    list($select, $from, $where, $having) = $query->query();
    // get and assert contact count
    $contactsResult = CRM_Core_DAO::executeQuery(sprintf('SELECT DISTINCT contact_a.id %s %s', $from, $where))->fetchAll();
    foreach ($contactsResult as $key => $value) {
      $contactsResult[$key] = $value['id'];
    }
    // assert the contacts count
    $this->assertEquals(1, count($contactsResult));
    // assert the contact IDs
    $expectedResult = [$contacts['id']];
    $this->checkArrayEquals($expectedResult, $contactsResult);
    // get and assert qill string
    $qill = trim(implode($query->getOperator(), CRM_Utils_Array::value(0, $query->qill())));
    $this->assertEquals("Contact Type In IndividualANDContact Subtype Like {$contactSubType}", $qill);
  }

  /**
   * Test to search based on Group type.
   * https://lab.civicrm.org/dev/core/issues/726
   */
  public function testContactSearchOnGroupType() {
    $groupTypes = $this->callAPISuccess('OptionValue', 'get', [
      'return' => ["id", "name"],
      'option_group_id' => "group_type",
    ])['values'];
    $groupTypes = array_column($groupTypes, 'id', 'name');

    // Create group with empty group type as Access Control.
    $groupId = $this->groupCreate([
      'group_type' => [
        $groupTypes['Access Control'] => 1,
      ],
    ]);
    // Add random 5 contacts to a group.
    $this->groupContactCreate($groupId, 5);

    // Find Contacts of Group type == Access Control
    $formValues['group_type'] = $groupTypes['Access Control'];
    CRM_Contact_BAO_Query::convertFormValues($formValues);
    $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($formValues));
    list($select, $from, $where, $having) = $query->query();
    // get and assert contact count
    $contactsResult = CRM_Core_DAO::executeQuery(sprintf('SELECT DISTINCT contact_a.id %s %s', $from, $where));
    // assert the contacts count
    $this->assertEquals(5, $contactsResult->N);

    // Find Contacts of Group type == Mailing List
    $formValues['group_type'] = $groupTypes['Mailing List'];
    CRM_Contact_BAO_Query::convertFormValues($formValues);
    $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($formValues));
    list($select, $from, $where, $having) = $query->query();
    // get and assert contact count
    $contactsResult = CRM_Core_DAO::executeQuery(sprintf('SELECT DISTINCT contact_a.id %s %s', $from, $where));
    // assert the contacts count
    $this->assertEquals(0, $contactsResult->N);
  }

}
