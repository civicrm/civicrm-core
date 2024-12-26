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
   *
   * @throws \CRM_Core_Exception
   */
  public function testContactCustomJoin(): void {
    $customGroup = CustomGroup::create()->setValues([
      'name' => 'D - Identification_20',
      'table_name' => 'civicrm_value_demographics',
      'title' => 'D - Identification',
      'extends' => 'Individual',
    ])->execute();
    CustomGroup::create()->setValues([
      'name' => 'other',
      'title' => 'other',
      'extends' => 'Individual',
    ])->execute();
    \CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_group SET name = 'D - Identification_20' WHERE id = %1", [1 => [$customGroup[0]['id'], 'Integer']]);
    $customField = CustomField::create()->setValues([
      'label' => 'Test field',
      'name' => 'test field',
      'custom_group_id' => $customGroup[0]['id'],
      'html_type' => 'Text',
      'data_type' => 'String',
    ])->execute();
    \CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_field SET name = 'D - Identification_20' WHERE id = %1", [1 => [$customField[0]['id'], 'Integer']]);
    CustomField::create()->setValues([
      'label' => 'other',
      'name' => 'other',
      'custom_group_id:name' => 'other',
      'html_type' => 'Text',
      'data_type' => 'String',
    ])->execute();
    $contactID = Contact::create()->setValues([
      'contact_type' => 'Individual',
      'first_name' => 'Ben',
      'other.other' => 'other',
      'D - Identification_20.D - Identification_20' => 10,
    ])->execute()->first()['id'];
    $this->assertEquals(10, Contact::get()->addSelect('*')
      ->addSelect('D - Identification_20.D - Identification_20')
      ->addWhere('id', '=', $contactID)
      ->execute()->first()['D - Identification_20.D - Identification_20']);

    // Test that calling a get with custom.* does not fatal.
    // Ideally we would also check it returns our field - but so far I haven't
    // figured out how to make it do that - so this maintains the prior level of cover.
    Contact::get()
      ->addSelect('*')
      ->addSelect('custom.*')
      ->addWhere('id', '=', $contactID)
      ->execute()->first();
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

  /**
   * Ensures we can join two entities with a custom field compared to a core field in the ON clause
   */
  public function testJoinWithCustomFieldComparedToCoreFieldInOnClause(): void {
    CustomGroup::create(FALSE)
      ->addValue('extends', 'Activity')
      ->addValue('title', 'a_set')
      ->addChain('field', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('label', 'a_field')
        ->addValue('html_type', 'Text')
      )
      ->execute();
    $cid = $this->saveTestRecords('Contact', ['records' => 3])->column('id');
    $activities = $this->saveTestRecords('Activity', [
      'records' => [
        ['source_contact_id' => $cid[0], 'subject' => 'yes match', 'a_set.a_field' => 'yes match'],
        ['source_contact_id' => $cid[1], 'subject' => 'yes match', 'a_set.a_field' => 'nope no match'],
        ['source_contact_id' => $cid[2], 'subject' => 'nope no match', 'a_set.a_field' => 'yes match'],
      ],
    ]);
    $result = Contact::get(FALSE)
      ->addSelect('id', 'activity.id')
      ->addWhere('id', 'IN', $cid)
      ->addJoin('Activity AS activity', 'LEFT', 'ActivityContact',
        ['id', '=', 'activity.contact_id'],
        ['activity.record_type_id:name', '=', '"Activity Source"'],
        ['activity.a_set.a_field', '=', 'activity.subject']
      )
      ->addOrderBy('id')
      ->execute();
    $this->assertCount(3, $result);
    $this->assertEquals($activities[0]['id'], $result[0]['activity.id']);
    $this->assertNull($result[1]['activity.id']);
    $this->assertNull($result[2]['activity.id']);
  }

}
