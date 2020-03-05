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


namespace api\v4\Entity;

use api\v4\UnitTestCase;
use Civi\Api4\Contact;

/**
 * @group headless
 */
class SavedSearchTest extends UnitTestCase {

  public function testApi4SmartGroup() {
    $in = Contact::create()->setCheckPermissions(FALSE)->addValue('first_name', 'yes')->addValue('do_not_phone', TRUE)->execute()->first();
    $out = Contact::create()->setCheckPermissions(FALSE)->addValue('first_name', 'no')->addValue('do_not_phone', FALSE)->execute()->first();

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
        'group' => ['Group', 'create', ['values' => ['title' => 'Hello Test', 'saved_search_id' => '$id']], 0],
      ],
    ])->first();

    // Oops we don't have an api4 syntax yet for selecting contacts in a group.
    $ins = civicrm_api3('Contact', 'get', ['group' => $savedSearch['group']['name'], 'options' => ['limit' => 0]]);
    $this->assertArrayHasKey($in['id'], $ins['values']);
    $this->assertArrayNotHasKey($out['id'], $ins['values']);
  }

}
