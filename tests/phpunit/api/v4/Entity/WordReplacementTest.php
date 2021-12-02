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

/**
 * @group headless
 */
class WordReplacementTest extends UnitTestCase {

  public function testDefaults() {
    $create = \Civi\Api4\WordReplacement::create(FALSE)
      ->addValue('find_word', 'Foo')
      ->addValue('replace_word', 'Bar')
      ->execute()
      ->first();

    $result = \Civi\Api4\WordReplacement::get(FALSE)
      ->addWhere('id', '=', $create['id'])
      ->execute()->first();
    $this->assertTrue($result['is_active']);
    $this->assertEquals('wildcardMatch', $result['match_type']);
    $this->assertEquals(\CRM_Core_Config::domainID(), $result['domain_id']);
  }

}
