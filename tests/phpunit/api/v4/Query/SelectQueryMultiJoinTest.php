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


namespace api\v4\Query;

use Civi\Api4\Contact;
use Civi\Api4\Email;
use api\v4\UnitTestCase;

/**
 * Class SelectQueryMultiJoinTest
 * @package api\v4\Query
 * @group headless
 */
class SelectQueryMultiJoinTest extends UnitTestCase {

  public function setUpHeadless() {
    $this->cleanup(['tablesToTruncate' => ['civicrm_contact', 'civicrm_email']]);
    $this->loadDataSet('MultiContactMultiEmail');
    return parent::setUpHeadless();
  }

  public function testOneToManySelect() {
    $results = Contact::get()
      ->addSelect('emails.email')
      ->execute()
      ->indexBy('id')
      ->getArrayCopy();

    $firstContactId = $this->getReference('test_contact_1')['id'];
    $secondContactId = $this->getReference('test_contact_2')['id'];

    $firstContact = $results[$firstContactId];
    $secondContact = $results[$secondContactId];
    $firstContactEmails = array_column($firstContact['emails'], 'email');
    $secondContactEmails = array_column($secondContact['emails'], 'email');

    $expectedFirstEmails = [
      'test_contact_one_home@fakedomain.com',
      'test_contact_one_work@fakedomain.com',
    ];
    $expectedSecondEmails = [
      'test_contact_two_home@fakedomain.com',
      'test_contact_two_work@fakedomain.com',
    ];

    $this->assertEquals($expectedFirstEmails, $firstContactEmails);
    $this->assertEquals($expectedSecondEmails, $secondContactEmails);
  }

  public function testManyToOneSelect() {
    $results = Email::get()
      ->addSelect('contact.display_name')
      ->execute()
      ->indexBy('id')
      ->getArrayCopy();

    $firstEmail = $this->getReference('test_email_1');
    $secondEmail = $this->getReference('test_email_2');
    $thirdEmail = $this->getReference('test_email_3');
    $fourthEmail = $this->getReference('test_email_4');
    $firstContactEmailIds = [$firstEmail['id'], $secondEmail['id']];
    $secondContactEmailIds = [$thirdEmail['id'], $fourthEmail['id']];

    foreach ($results as $id => $email) {
      $displayName = $email['contact.display_name'];
      if (in_array($id, $firstContactEmailIds)) {
        $this->assertEquals('First Contact', $displayName);
      }
      elseif (in_array($id, $secondContactEmailIds)) {
        $this->assertEquals('Second Contact', $displayName);
      }
    }
  }

}
