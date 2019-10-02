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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */


namespace api\v4\Entity;

use Civi\Api4\Contact;
use Civi\Api4\OptionValue;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class ContactJoinTest extends UnitTestCase {

  public function setUpHeadless() {
    $relatedTables = [
      'civicrm_address',
      'civicrm_email',
      'civicrm_phone',
      'civicrm_openid',
      'civicrm_im',
      'civicrm_website',
      'civicrm_activity',
      'civicrm_activity_contact',
    ];

    $this->cleanup(['tablesToTruncate' => $relatedTables]);
    $this->loadDataSet('SingleContact');

    return parent::setUpHeadless();
  }

  public function testContactJoin() {

    $contact = $this->getReference('test_contact_1');
    $entitiesToTest = ['Address', 'OpenID', 'IM', 'Website', 'Email', 'Phone'];

    foreach ($entitiesToTest as $entity) {
      $results = civicrm_api4($entity, 'get', [
        'where' => [['contact_id', '=', $contact['id']]],
        'select' => ['contact.display_name', 'contact.id'],
      ]);
      foreach ($results as $result) {
        $this->assertEquals($contact['id'], $result['contact.id']);
        $this->assertEquals($contact['display_name'], $result['contact.display_name']);
      }
    }
  }

  public function testJoinToPCMWillReturnArray() {
    $contact = Contact::create()->setValues([
      'preferred_communication_method' => [1, 2, 3],
      'contact_type' => 'Individual',
      'first_name' => 'Test',
      'last_name' => 'PCM',
    ])->execute()->first();

    $fetchedContact = Contact::get()
      ->addWhere('id', '=', $contact['id'])
      ->addSelect('preferred_communication_method')
      ->execute()
      ->first();

    $this->assertCount(3, $fetchedContact["preferred_communication_method"]);
  }

  public function testJoinToPCMOptionValueWillShowLabel() {
    $options = OptionValue::get()
      ->addWhere('option_group.name', '=', 'preferred_communication_method')
      ->execute()
      ->getArrayCopy();

    $optionValues = array_column($options, 'value');
    $labels = array_column($options, 'label');

    $contact = Contact::create()->setValues([
      'preferred_communication_method' => $optionValues,
      'contact_type' => 'Individual',
      'first_name' => 'Test',
      'last_name' => 'PCM',
    ])->execute()->first();

    $contact2 = Contact::create()->setValues([
      'preferred_communication_method' => $optionValues,
      'contact_type' => 'Individual',
      'first_name' => 'Test',
      'last_name' => 'PCM2',
    ])->execute()->first();

    $contactIds = array_column([$contact, $contact2], 'id');

    $fetchedContact = Contact::get()
      ->addWhere('id', 'IN', $contactIds)
      ->addSelect('preferred_communication_method.label')
      ->execute()
      ->first();

    $preferredMethod = $fetchedContact['preferred_communication_method'];
    $returnedLabels = array_column($preferredMethod, 'label');

    $this->assertEquals($labels, $returnedLabels);
  }

}
