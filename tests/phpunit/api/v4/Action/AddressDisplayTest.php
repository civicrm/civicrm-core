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

namespace api\v4\Action;

use api\v4\Api4TestBase;
use Civi\Api4\Contact;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class AddressDisplayTest extends Api4TestBase implements TransactionalInterface {

  public function testDisplayText(): void {
    $contactId = $this->createTestRecord('Contact')['id'];
    $this->createTestRecord('Address', [
      'contact_id' => $contactId,
      'street_address' => '123 Main St',
      'city' => 'Springfield',
      'postal_code' => '12345',
      'postal_code_suffix' => '67',
      'country_id:name' => 'US',
      'state_province_id:abbr' => 'CA',
    ]);

    $result = Contact::get(FALSE)
      ->addJoin('Address AS address', 'INNER', ['id', '=', 'address.contact_id'])
      ->addSelect('address.display')
      ->addWhere('id', '=', $contactId)
      ->execute()->single();

    $this->assertStringContainsString('123 Main St', $result['address.display']);
    $this->assertStringContainsString('Springfield, CA 12345-67', $result['address.display']);
    $this->assertStringEndsWith('United States', $result['address.display']);
    $this->assertStringNotContainsString('{', $result['address.display']);
    $this->assertSame(trim($result['address.display']), $result['address.display']);
  }

}
