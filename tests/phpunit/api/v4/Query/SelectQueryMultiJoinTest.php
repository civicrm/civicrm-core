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


namespace api\v4\Query;

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

  public function testManyToOneSelect() {
    $results = Email::get()
      ->addSelect('contact_id.display_name')
      ->execute()
      ->indexBy('id');

    $firstEmail = $this->getReference('test_email_1');
    $secondEmail = $this->getReference('test_email_2');
    $thirdEmail = $this->getReference('test_email_3');
    $fourthEmail = $this->getReference('test_email_4');
    $firstContactEmailIds = [$firstEmail['id'], $secondEmail['id']];
    $secondContactEmailIds = [$thirdEmail['id'], $fourthEmail['id']];

    foreach ($results as $id => $email) {
      $displayName = $email['contact_id.display_name'];
      if (in_array($id, $firstContactEmailIds)) {
        $this->assertEquals('First Contact', $displayName);
      }
      elseif (in_array($id, $secondContactEmailIds)) {
        $this->assertEquals('Second Contact', $displayName);
      }
    }
  }

}
