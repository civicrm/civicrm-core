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
    $result = Contact::getFields(FALSE)->addWhere('type', '=', 'Field')->execute();
    $json = json_encode($result);
    $this->assertStringStartsWith('[{"', $json);
    $this->assertTrue(is_array(json_decode($json)));
  }

  /**
   * Knowing that the db layer HTML-encodes strings, we want to test
   * that this ugliness is hidden from us as users of the API.
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-11532
   * @see https://lab.civicrm.org/dev/core/-/issues/1328
   */
  public function testNoDataCorruptionThroughEncoding() {

    $original = 'hello < you';
    $result = Contact::create(FALSE)
      ->setValues(['display_name' => $original])
      ->execute()->first();
    $this->assertEquals($original, $result['display_name'],
      "The value returned from Contact.create is different to the value sent."
    );

    $result = Contact::update(FALSE)
      ->addWhere('id', '=', $result['id'])
      ->setValues(['display_name' => $original])
      ->execute()->first();
    $this->assertEquals($original, $result['display_name'],
      "The value returned from Contact.update is different to the value sent."
    );

    $result = Contact::get(FALSE)
      ->addWhere('id', '=', $result['id'])
      ->execute()->first();
    $this->assertEquals($original, $result['display_name'],
      "The value returned from Contact.get is different to the value sent."
    );
  }

}
