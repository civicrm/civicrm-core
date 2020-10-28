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
use Civi\Api4\Email;

/**
 * @group headless
 */
class SavedSearchTest extends UnitTestCase {

  public function testContactSmartGroup() {
    $in = Contact::create(FALSE)->addValue('first_name', 'yes')->addValue('do_not_phone', TRUE)->execute()->first();
    $out = Contact::create(FALSE)->addValue('first_name', 'no')->addValue('do_not_phone', FALSE)->execute()->first();

    $savedSearch = civicrm_api4('SavedSearch', 'create', [
      'values' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'where' => [
            ['do_not_phone', '=', TRUE],
          ],
        ],
      ],
      'chain' => [
        'group' => ['Group', 'create', ['values' => ['title' => 'Contact Test', 'saved_search_id' => '$id']], 0],
      ],
    ])->first();

    // Oops we don't have an api4 syntax yet for selecting contacts in a group.
    $ins = civicrm_api3('Contact', 'get', ['group' => $savedSearch['group']['name'], 'options' => ['limit' => 0]]);
    $this->assertEquals(1, count($ins['values']));
    $this->assertArrayHasKey($in['id'], $ins['values']);
    $this->assertArrayNotHasKey($out['id'], $ins['values']);
  }

  public function testEmailSmartGroup() {
    $in = Contact::create(FALSE)->addValue('first_name', 'yep')->execute()->first();
    $out = Contact::create(FALSE)->addValue('first_name', 'nope')->execute()->first();
    $email = uniqid() . '@' . uniqid();
    Email::create(FALSE)->addValue('email', $email)->addValue('contact_id', $in['id'])->execute();

    $savedSearch = civicrm_api4('SavedSearch', 'create', [
      'values' => [
        'api_entity' => 'Email',
        'api_params' => [
          'version' => 4,
          'select' => ['contact_id'],
          'where' => [
            ['email', '=', $email],
          ],
        ],
      ],
      'chain' => [
        'group' => ['Group', 'create', ['values' => ['title' => 'Email Test', 'saved_search_id' => '$id']], 0],
      ],
    ])->first();

    // Oops we don't have an api4 syntax yet for selecting contacts in a group.
    $ins = civicrm_api3('Contact', 'get', ['group' => $savedSearch['group']['name'], 'options' => ['limit' => 0]]);
    $this->assertEquals(1, count($ins['values']));
    $this->assertArrayHasKey($in['id'], $ins['values']);
    $this->assertArrayNotHasKey($out['id'], $ins['values']);
  }

  public function testSmartGroupWithHaving() {
    $in = Contact::create(FALSE)->addValue('first_name', 'yes')->addValue('last_name', 'siree')->execute()->first();
    $in2 = Contact::create(FALSE)->addValue('first_name', 'yessir')->addValue('last_name', 'ee')->execute()->first();
    $out = Contact::create(FALSE)->addValue('first_name', 'yess')->execute()->first();

    $savedSearch = civicrm_api4('SavedSearch', 'create', [
      'values' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'CONCAT(first_name, last_name) AS whole_name'],
          'where' => [
            ['id', '>=', $in['id']],
          ],
          'having' => [
            ['whole_name', '=', 'yessiree'],
          ],
        ],
      ],
      'chain' => [
        'group' => ['Group', 'create', ['values' => ['title' => 'Having Test', 'saved_search_id' => '$id']], 0],
      ],
    ])->first();

    // Oops we don't have an api4 syntax yet for selecting contacts in a group.
    $ins = civicrm_api3('Contact', 'get', ['group' => $savedSearch['group']['name'], 'options' => ['limit' => 0]]);
    $this->assertCount(2, $ins['values']);
    $this->assertArrayHasKey($in['id'], $ins['values']);
    $this->assertArrayHasKey($in2['id'], $ins['values']);
    $this->assertArrayNotHasKey($out['id'], $ins['values']);
  }

}
