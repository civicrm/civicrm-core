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
use Civi\Api4\Contact;

/**
 * @group headless
 */
class GetExtraFieldsTest extends UnitTestCase {

  public function testGetFieldsByContactType() {
    $getFields = Contact::getFields()->setCheckPermissions(FALSE)->addSelect('name')->setIncludeCustom(FALSE);

    $baseFields = array_column(\CRM_Contact_BAO_Contact::fields(), 'name');
    $returnedFields = $getFields->execute()->column('name');
    $notReturned = array_diff($baseFields, $returnedFields);

    // With no contact_type specified, all fields should be returned
    $this->assertEmpty($notReturned);

    $individualFields = $getFields->setValues(['contact_type' => 'Individual'])->execute()->column('name');
    $this->assertNotContains('sic_code', $individualFields);
    $this->assertNotContains('contact_type', $individualFields);
    $this->assertContains('first_name', $individualFields);

    $organizationFields = $getFields->setValues(['contact_type' => 'Organization'])->execute()->column('name');
    $this->assertContains('sic_code', $organizationFields);
    $this->assertNotContains('contact_type', $organizationFields);
    $this->assertNotContains('first_name', $organizationFields);
    $this->assertNotContains('household_name', $organizationFields);
  }

}
