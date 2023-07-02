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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4\Custom;

use Civi\Api4\Contact;
use Civi\Api4\CustomGroup;
use Civi\Api4\CustomField;
use Civi\Api4\Participant;

/**
 * @group headless
 */
class ContactCustomJoinTest extends CustomTestBase {

  /**
   * Add test to ensure that in the very unusual and not really supported situation where there is a space in the
   * custom group machine name. This is not supported but has been seen in the wild and as such we have this test to lock in the fix for dev/mail#103
   */
  public function testContactCustomJoin() {
    $customGroup = CustomGroup::create()->setValues([
      'name' => 'D - Identification_20',
      'table_name' => 'civicrm_value_demographics',
      'title' => 'D - Identification',
      'extends' => 'Individual',
    ])->execute();
    \CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_group SET name = 'D - Identification_20' WHERE id = %1", [1 => [$customGroup[0]['id'], 'Integer']]);
    $customField = CustomField::create()->setValues([
      'label' => 'Test field',
      'custom_group_id' => $customGroup[0]['id'],
      'html_type' => 'Text',
      'data_type' => 'String',
    ])->execute();
    Contact::get()->addSelect('*')->addSelect('custom.*')->execute();
  }

  /**
   * Ensures we can join two entities with a custom field in the ON clause
   */
  public function testJoinWithCustomFieldInOnClause(): void {
    CustomGroup::create(FALSE)
      ->addValue('extends', 'Participant')
      ->addValue('title', 'p_set')
      ->addChain('field', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('label', 'p_field')
        ->addValue('html_type', 'Text')
      )
      ->execute();
    $cid = $this->saveTestRecords('Contact', ['records' => 3])->column('id');
    $this->saveTestRecords('Participant', [
      'records' => [
        ['contact_id' => $cid[0], 'p_set.p_field' => 'Value A'],
        ['contact_id' => $cid[1], 'p_set.p_field' => 'Value B'],
        ['contact_id' => $cid[2], 'p_set.p_field' => 'Value A'],
      ],
    ]);
    $results = Participant::get(FALSE)
      ->addSelect('id')
      ->addWhere('p_set.p_field', '=', 'Value A')
      ->execute();
    $this->assertCount(2, $results);
    $results = Contact::get(FALSE)
      ->addSelect('id')
      ->addJoin('Participant AS participant', 'INNER',
        ['id', '=', 'participant.contact_id'],
        ['participant.p_set.p_field', '=', '"Value A"'],
      )
      ->execute();
    $this->assertCount(2, $results);
  }

}
