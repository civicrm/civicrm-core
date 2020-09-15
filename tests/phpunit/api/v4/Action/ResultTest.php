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


namespace api\v4\Action;

use Civi\Api4\Contact;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class ResultTest extends UnitTestCase {

  public function testJsonSerialize() {
    $result = Contact::getFields(FALSE)->setIncludeCustom(FALSE)->execute();
    $json = json_encode($result);
    $this->assertStringStartsWith('[{"', $json);
    $this->assertTrue(is_array(json_decode($json)));
  }

}
