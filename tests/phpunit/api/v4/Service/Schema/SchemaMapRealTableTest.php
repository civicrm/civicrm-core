<?php

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
