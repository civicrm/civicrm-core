<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2020                                |
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
 * @copyright CiviCRM LLC (c) 2004-2020
 * $Id$
 *
 */

namespace api\v4\Entity;

use Civi\Api4\ActivityContact;
use Civi\Api4\Contact;
use api\v4\UnitTestCase;
use Civi\Test\TransactionalInterface;

/**
 * Assert that interchanging data between APIv3 and APIv4 yields consistent
 * encodings.
 *
 * @group headless
 */
class ContactInterchangeTest extends UnitTestCase implements TransactionalInterface {

  public function getExamples() {
    $apiWriters = [
      'create_3',
      'create_4',
    ];
    $apiReaderSets = [
      ['readNameById_3', 'readNameByValueEq_3'],
      ['readNameById_4', 'readNameByValueEq_4', 'readNameByValueIn_4'],
      ['readNameByActSubjectChain_3', /* 'readNameByActSubjectJoin_3' */],
      ['readNameByActSubjectChain_4', 'readNameByActSubjectJoin_4'],
      [/*'readNameByActDetailsChain_3',*/ 'readNameByActDetailsJoin_3'],
      ['readNameByActDetailsChain_4', 'readNameByActDetailsJoin_4'],
    ];
    $strings = [
      // Values chosen for backwards compatibility.
      ['api' => 'Little Bobby :> & :<', 'db' => 'Little Bobby :&gt; & :&lt;'],
      ['api' => 'Little Bobby &amp; Bob', 'db' => 'Little Bobby &amp; Bob'],
      ['api' => 'It\'s', 'db' => 'It\'s'],
    ];

    $cases = \CRM_Utils_Array::product([
      'w' => $apiWriters,
      'r' => $apiReaderSets,
      'strs' => $strings,
    ]);
    foreach ($cases as $case) {
      yield [$case['w'], $case['r'], $case['strs']];
    }
  }

  /**
   * Use the given $writer to create a row with some funny data.
   * Then use the given $reader to load the row.
   * Assert that the funny data looks the same.
   *
   * @dataProvider getExamples
   */
  public function testReadWriteAPI($writer, $readers, $strs) {
    $caseName = sprintf("writer=%s, strs.api=%s", $writer, $strs['api']);

    $cid = call_user_func([$this, $writer], $strs);

    $dbGet = \CRM_Core_DAO::singleValueQuery('SELECT first_name FROM civicrm_contact WHERE id = %1', [
      1 => [$cid, 'Positive'],
    ]);
    $this->assertEquals($strs['db'], $dbGet, "Check DB content ($caseName)");

    $this->assertNotEmpty($readers);
    foreach ($readers as $reader) {
      $get = call_user_func([$this, $reader], $cid, $strs);
      // $get = $this->autoWrapException([$this, $reader], [$cid, $strs], "Check API content (reader=$reader $caseName)");
      $this->assertEquals($strs['api'], $get, "Check API content (reader=$reader $caseName)");
    }
  }

  private function autoWrapException($callable, $args, $message) {
    try {
      return call_user_func_array($callable, $args);
    }
    catch (\Exception $e) {
      throw new \Exception($message . $e->getMessage(), 0, $e);
    }
  }

  public function create_3($strs) {
    $contact = civicrm_api3('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => $strs['api'],
      'api.Activity.create' => [
        'source_contact_id' => '$value.id',
        'subject' => $strs['api'],
        'details' => $strs['api'],
        'activity_type_id' => 2,
      ],
    ]);
    return $contact['id'];
  }

  public function create_4($strs) {
    $contact = civicrm_api4('Contact', 'create', [
      'values' => [
        'contact_type' => 'Individual',
        'first_name' => $strs['api'],
      ],
      'chain' => [
        'my_call' => [
          'Activity',
          'create',
          [
            'values' => [
              'source_contact_id' => '$id',
              'subject' => $strs['api'],
              'details' => $strs['api'],
              'activity_type_id' => 2,
            ],
          ],
        ],
      ],
    ]);
    return $contact->first()['id'];
  }

  public function readNameById_3($cid, $strs) {
    $get = civicrm_api3('Contact', 'getsingle', [
      'id' => $cid,
      'return' => 'first_name',
    ]);
    return $get['first_name'];
  }

  public function readNameByValueEq_3($cid, $strs) {
    $get = civicrm_api3('Contact', 'getsingle', [
      'first_name' => $strs['api'],
    ]);
    return $get['first_name'];
  }

  public function readNameById_4($cid, $strs) {
    $get = Contact::get()
      ->addWhere('id', '=', $cid)
      ->addSelect('first_name')
      ->execute();
    return $get->first()['first_name'];
  }

  public function readNameByValueEq_4($cid, $strs) {
    $get = Contact::get()
      ->addWhere('first_name', '=', $strs['api'])
      ->addSelect('first_name')
      ->execute();
    return $get->first()['first_name'];
  }

  public function readNameByValueIn_4($cid, $strs) {
    $get = Contact::get()
      ->addWhere('first_name', 'IN', [$strs['api']])
      ->addSelect('first_name')
      ->execute();
    return $get->first()['first_name'];
  }

  public function readNameByActSubjectJoin_3($cid, $strs) {
    $get = civicrm_api3('ActivityContact', 'getsingle', [
      'record_type_id' => 'Activity Source',
      'activity_id.subject' => $strs['api'],
      'return' => ['contact_id.first_name'],
    ]);
    return $get['contact_id.first_name'];
  }

  public function readNameByActSubjectChain_3($cid, $strs) {
    $get = civicrm_api3('Activity', 'getsingle', [
      'subject' => $strs['api'],
      'api.ActivityContact.getsingle' => [
        'activity_id' => '$value.id',
        'record_type_id' => 'Activity Source',
        'api.Contact.getsingle' => [
          'id' => '$value.contact_id',
          'return' => 'first_name',
        ],
      ],
      'return' => ['id'],
    ]);
    return $get['api.ActivityContact.getsingle']['api.Contact.getsingle']['first_name'];
  }

  public function readNameByActSubjectJoin_4($cid, $strs) {
    $get = ActivityContact::get()
      ->addWhere('activity_contacts.label', '=', 'Activity Source')
      ->addWhere('activity.subject', '=', $strs['api'])
      ->addSelect('contact.first_name')
      ->execute();
    return $get->first()['contact.first_name'];
  }

  public function readNameByActSubjectChain_4($cid, $strs) {
    $get = ActivityContact::get()
      ->addWhere('activity_contacts.label', '=', 'Activity Source')
      ->addWhere('activity.subject', '=', $strs['api'])
      ->setSelect(['activity_id', 'contact_id'])
      ->setChain([
        'the_contact' => [
          'Contact',
          'get',
          ['where' => [['id', '=', '$contact_id']], 'select' => ['first_name']],
          0,
        ],
      ])
      ->execute();
    $result = $get->first();
    return $result['the_contact']['first_name'];
  }

  public function readNameByActDetailsJoin_3($cid, $strs) {
    $get = civicrm_api3('ActivityContact', 'getsingle', [
      'record_type_id' => 'Activity Source',
      'activity_id.details' => $strs['api'],
      'return' => ['contact_id.first_name'],
    ]);
    return $get['contact_id.first_name'];
  }

  public function readNameByActDetailsChain_3($cid, $strs) {
    $get = civicrm_api3('Activity', 'getsingle', [
      'details' => $strs['api'],
      'api.ActivityContact.getsingle' => [
        'activity_id' => '$value.id',
        'record_type_id' => 'Activity Source',
        'api.Contact.getsingle' => [
          'id' => '$value.contact_id',
          'return' => 'first_name',
        ],
      ],
      'return' => ['id'],
    ]);
    return $get['api.ActivityContact.getsingle']['api.Contact.getsingle']['first_name'];
  }

  public function readNameByActDetailsJoin_4($cid, $strs) {
    $get = ActivityContact::get()
      ->addWhere('activity_contacts.label', '=', 'Activity Source')
      ->addWhere('activity.details', '=', $strs['api'])
      ->addSelect('contact.first_name')
      ->execute();
    return $get->first()['contact.first_name'];
  }

  public function readNameByActDetailsChain_4($cid, $strs) {
    $get = ActivityContact::get()
      ->addWhere('activity_contacts.label', '=', 'Activity Source')
      ->addWhere('activity.details', '=', $strs['api'])
      ->setSelect(['activity_id', 'contact_id'])
      ->setChain([
        'the_contact' => [
          'Contact',
          'get',
          ['where' => [['id', '=', '$contact_id']], 'select' => ['first_name']],
          0,
        ],
      ])
      ->execute();
    $result = $get->first();
    return $result['the_contact']['first_name'];
  }

}
