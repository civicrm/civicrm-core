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


namespace api\v4\Action;

use Civi\Api4\Contact;

/**
 * @group headless
 */
class ContactChecksumTest extends \api\v4\UnitTestCase {

  public function testGetChecksum() {
    $contact = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Check')
      ->addValue('last_name', 'Sum')
      ->addChain('cs', Contact::getChecksum()->setContactId('$id')->setTtl(500), 0)
      ->execute()
      ->first();

    $result = Contact::validateChecksum()
      ->setContactId($contact['id'])
      ->setChecksum($contact['cs']['checksum'])
      ->execute()
      ->first();

    $this->assertTrue($result['valid']);
  }

  public function testValidateChecksum() {
    $cid = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Checker')
      ->addValue('last_name', 'Sum')
      ->execute()
      ->first()['id'];

    $goodCs = \CRM_Contact_BAO_Contact_Utils::generateChecksum($cid, NULL, 500);
    $badCs = \CRM_Contact_BAO_Contact_Utils::generateChecksum($cid, strtotime('now - 1 week'), 1);

    $result1 = Contact::validateChecksum()
      ->setContactId($cid)
      ->setChecksum($goodCs)
      ->execute()
      ->first();
    $this->assertTrue($result1['valid']);

    $result2 = Contact::validateChecksum()
      ->setContactId($cid)
      ->setChecksum($badCs)
      ->execute()
      ->first();
    $this->assertFalse($result2['valid']);
  }

}
