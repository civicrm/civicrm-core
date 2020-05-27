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


namespace api\v4\Service\Schema;

use api\v4\UnitTestCase;

/**
 * @group headless
 */
class SchemaMapRealTableTest extends UnitTestCase {

  public function testAutoloadWillPopulateTablesByDefault() {
    $map = \Civi::container()->get('schema_map');
    $this->assertNotEmpty($map->getTables());
  }

  public function testSimplePathWillExist() {
    $map = \Civi::container()->get('schema_map');
    $path = $map->getPath('civicrm_contact', 'emails');
    $this->assertCount(1, $path);
  }

}
