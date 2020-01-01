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
 * $Id$
 *
 */


namespace api\v4\Action;

use api\v4\UnitTestCase;
use Civi\Api4\Address;
use Civi\Api4\Contact;

/**
 * @group headless
 */
class GetExtraFieldsTest extends UnitTestCase {

  public function testBAOFieldsWillBeReturned() {
    $returnedFields = Contact::getFields()
      ->execute()
      ->getArrayCopy();

    $baseFields = \CRM_Contact_BAO_Contact::fields();
    $baseFieldNames = array_column($baseFields, 'name');
    $returnedFieldNames = array_column($returnedFields, 'name');
    $notReturned = array_diff($baseFieldNames, $returnedFieldNames);

    $this->assertEmpty($notReturned);
  }

  public function testGetOptionsAddress() {
    $getFields = Address::getFields()->setCheckPermissions(FALSE)->addWhere('name', '=', 'state_province_id')->setLoadOptions(TRUE);

    $usOptions = $getFields->setValues(['country_id' => 1228])->execute()->first();

    $this->assertContains('Alabama', $usOptions['options']);
    $this->assertNotContains('Alberta', $usOptions['options']);

    $caOptions = $getFields->setValues(['country_id' => 1039])->execute()->first();

    $this->assertNotContains('Alabama', $caOptions['options']);
    $this->assertContains('Alberta', $caOptions['options']);
  }

}
