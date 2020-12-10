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

use Civi\Api4\Email;
use Civi\Api4\IM;
use Civi\Api4\Phone;
use Civi\Api4\Address;
use Civi\Api4\OpenID;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class IsPrimaryTest extends UnitTestCase {

  /**
   * Test that creating a location entity or deleting one re-assigns is_primary correctly.
   */
  public function testPrimaryHandling() {
    $contactID = self::createEntity(['type' => 'Individual'])['id'];
    // Create an entity of each type.
    Email::create()->setValues(['email' => 'b@example.com', 'contact_id' => $contactID])->execute();
    Phone::create()->setValues(['phone' => '123', 'contact_id' => $contactID])->execute();
    IM::create()->setValues(['name' => 'im', 'contact_id' => $contactID])->execute();
    OpenID::create()->setValues(['openid' => 'openid', 'contact_id' => $contactID])->execute();
    $firstAddressID = Address::create()->setValues(['street_name' => '1 sesame street', 'contact_id' => $contactID])->execute()->first()['id'];
    $this->assertValidLocations();

    // Create an second entity of each type - demoting the first
    Email::create()->setValues(['email' => 'b2@example.com', 'contact_id' => $contactID, 'is_primary' => TRUE])->execute();
    Phone::create()->setValues(['phone' => '1232', 'contact_id' => $contactID, 'is_primary' => TRUE])->execute();
    IM::create()->setValues(['name' => 'im2', 'contact_id' => $contactID, 'is_primary' => TRUE])->execute();
    OpenID::create()->setValues(['openid' => 'openid2', 'contact_id' => $contactID, 'is_primary' => TRUE])->execute();
    Address::create()->setValues(['street_name' => '2 sesame street', 'contact_id' => $contactID, 'is_primary' => TRUE])->execute();
    $this->assertValidLocations();
    $this->assertNotEquals($firstAddressID, Address::get()->addWhere('is_primary', '=', TRUE)->addWhere('contact_id', '=', $contactID)->execute()->first()['id']);

    // Update all the non-primaries
    // to is_primary TRUE.
    Email::update()->setValues(['is_primary' => TRUE])->addWhere('contact_id', '=', $contactID)->addWhere('is_primary', '=', FALSE)->execute();
    Phone::update()->setValues(['is_primary' => TRUE])->addWhere('contact_id', '=', $contactID)->addWhere('is_primary', '=', FALSE)->execute();
    IM::update()->setValues(['is_primary' => TRUE])->addWhere('contact_id', '=', $contactID)->addWhere('is_primary', '=', FALSE)->execute();
    OpenID::update()->setValues(['is_primary' => TRUE])->addWhere('contact_id', '=', $contactID)->addWhere('is_primary', '=', FALSE)->execute();
    Address::update()->setValues(['is_primary' => TRUE])->addWhere('contact_id', '=', $contactID)->addWhere('is_primary', '=', FALSE)->execute();
    $this->assertValidLocations();
    $this->assertEquals($firstAddressID, Address::get()->addWhere('is_primary', '=', TRUE)->addWhere('contact_id', '=', $contactID)->execute()->first()['id']);

    Email::delete()->addWhere('is_primary', '=', TRUE)->execute();
    Phone::delete()->addWhere('is_primary', '=', TRUE)->execute();
    IM::delete()->addWhere('is_primary', '=', TRUE)->execute();
    OpenID::delete()->addWhere('is_primary', '=', TRUE)->execute();
    Address::delete()->addWhere('is_primary', '=', TRUE)->execute();
    $this->assertValidLocations();
    $this->assertNotEquals($firstAddressID, Address::get()->addWhere('is_primary', '=', TRUE)->addWhere('contact_id', '=', $contactID)->execute()->first()['id']);

  }

  /**
   * Check that all location entities have exactly one primary.
   */
  protected function assertValidLocations() {
    $this->assertEquals(0, \CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM

(SELECT a1.contact_id
FROM civicrm_address a1
  LEFT JOIN civicrm_address a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE
  a1.is_primary = 1
  AND a2.id IS NOT NULL
  AND a1.contact_id IS NOT NULL
UNION
SELECT a1.contact_id
FROM civicrm_address a1
       LEFT JOIN civicrm_address a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE a1.is_primary = 0
  AND a2.id IS NULL
  AND a1.contact_id IS NOT NULL

UNION

SELECT a1.contact_id
FROM civicrm_email a1
       LEFT JOIN civicrm_email a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE
    a1.is_primary = 1
  AND a2.id IS NOT NULL
  AND a1.contact_id IS NOT NULL
UNION
SELECT a1.contact_id
FROM civicrm_email a1
       LEFT JOIN civicrm_email a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE a1.is_primary = 0
  AND a2.id IS NULL
  AND a1.contact_id IS NOT NULL

UNION

SELECT a1.contact_id
FROM civicrm_phone a1
       LEFT JOIN civicrm_phone a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE
    a1.is_primary = 1
  AND a2.id IS NOT NULL
  AND a1.contact_id IS NOT NULL
UNION
SELECT a1.contact_id
FROM civicrm_phone a1
       LEFT JOIN civicrm_phone a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE a1.is_primary = 0
  AND a2.id IS NULL
  AND a1.contact_id IS NOT NULL

UNION

SELECT a1.contact_id
FROM civicrm_im a1
       LEFT JOIN civicrm_im a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE
    a1.is_primary = 1
  AND a2.id IS NOT NULL
  AND a1.contact_id IS NOT NULL
UNION
SELECT a1.contact_id
FROM civicrm_im a1
       LEFT JOIN civicrm_im a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE a1.is_primary = 0
  AND a2.id IS NULL
  AND a1.contact_id IS NOT NULL

UNION

SELECT a1.contact_id
FROM civicrm_openid a1
       LEFT JOIN civicrm_openid a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE (a1.is_primary = 1 AND a2.id IS NOT NULL)
UNION

SELECT a1.contact_id
FROM civicrm_openid a1
       LEFT JOIN civicrm_openid a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE
    a1.is_primary = 1
  AND a2.id IS NOT NULL
  AND a1.contact_id IS NOT NULL
UNION
SELECT a1.contact_id
FROM civicrm_openid a1
       LEFT JOIN civicrm_openid a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE a1.is_primary = 0
  AND a2.id IS NULL
  AND a1.contact_id IS NOT NULL) as primary_descrepancies
    '));
  }

}
