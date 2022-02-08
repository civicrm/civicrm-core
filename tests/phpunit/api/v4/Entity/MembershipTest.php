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

use api\v4\UnitTestCase;
use Civi\Api4\Contact;
use Civi\Api4\Domain;
use Civi\Api4\MembershipType;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class MembershipTest extends UnitTestCase implements TransactionalInterface {

  public function testUpdateWeights() {
    $getValues = function($domain) {
      return MembershipType::get(FALSE)
        ->addWhere('domain_id.name', '=', $domain)
        ->addOrderBy('weight')
        ->execute()->indexBy('name')->column('weight');
    };

    // Create 2 domains. Control domain is to ensure updating one doesn't affect the other
    foreach (['controlDomain', 'experimentalDomain'] as $domain) {
      Domain::create(FALSE)
        ->addValue('name', $domain)
        ->addValue('version', \CRM_Utils_System::version())
        ->execute();
      $sampleData = [
        ['name' => 'One'],
        ['name' => 'Two'],
        ['name' => 'Three'],
        ['name' => 'Four'],
      ];
      MembershipType::save(FALSE)
        ->setRecords($sampleData)
        ->addDefault('domain_id.name', $domain)
        ->addDefault('financial_type_id', 1)
        ->addDefault('duration_unit', 'day')
        ->addDefault('period_type', 'rolling')
        ->addDefault('member_of_contact_id', Contact::create(FALSE)
          ->addValue('organization_name', $domain)->execute()->first()['id'])
        ->execute();
      $this->assertEquals(['One' => 1, 'Two' => 2, 'Three' => 3, 'Four' => 4], $getValues($domain));
    }

    // Move first option to third position
    MembershipType::update(FALSE)
      ->addWhere('domain_id.name', '=', 'experimentalDomain')
      ->addWhere('name', '=', 'One')
      ->addValue('weight', 3)
      ->execute();
    // Experimental domain should be updated, control domain should not
    $this->assertEquals(['Two' => 1, 'Three' => 2, 'One' => 3, 'Four' => 4], $getValues('experimentalDomain'));
    $this->assertEquals(['One' => 1, 'Two' => 2, 'Three' => 3, 'Four' => 4], $getValues('controlDomain'));

  }

  /**
   * Test getting options
   */
  public function testGetOptions(): void {
    $fields = MembershipType::getFields(FALSE)
      ->setLoadOptions(['name', 'id', 'label'])
      ->execute()->indexBy('name');
    $this->assertEquals('rolling', $fields['period_type']['options'][0]['name']);
    $this->assertEquals('rolling', $fields['period_type']['options'][0]['id']);
    $this->assertEquals('Rolling', $fields['period_type']['options'][0]['label']);
  }

}
