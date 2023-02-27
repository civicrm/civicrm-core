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


namespace api\v4\Entity;

use api\v4\Api4TestBase;

/**
 * @group headless
 */
class GroupContactTest extends Api4TestBase {

  public function testCreate() {
    $contact = $this->createTestRecord('Contact');
    $group = $this->createTestRecord('Group');
    $result = $this->createTestRecord('GroupContact', [
      'group_id' => $group['id'],
      'contact_id' => $contact['id'],
    ]);
    $this->assertEquals('Added', $result['status']);
  }

}
