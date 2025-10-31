<?php
namespace Civi\Search;

use api\v4\Api4TestBase;

require_once __DIR__ . '/../../../../../../tests/phpunit/api/v4/Api4TestBase.php';

/**
 * @group headless
 */
class MetaTest extends Api4TestBase {

  public function testCreateSqlName() {
    $examples = [];
    $examples['email_primary.email'] = ['email_primary_email', ''];
    $examples['contact.id'] = ['contact_id', ''];
    $examples['foo:bar'] = ['foo', 'bar'];
    $examples['ten456789.ten456789.ten456789.ten456789.ten456789.ten456789.ten456789'] = ['ten456789_ten456789_ten456789_ten456789_te0e429d24fc4314ed', ''];

    // WARNING: The formula lives in both Civi\Search\Meta and crmSearchAdmin.module.js. Keep synchronized!

    foreach ($examples as $input => $expected) {
      $actual = \Civi\Search\Meta::createSqlName($input);
      $this->assertEquals($expected, $actual, "Input ($input) should generated expected columns");

      $actual = \Civi\Search\Meta::createSqlName($input, 'my_custom');
      $this->assertEquals(['my_custom', $expected[1]], $actual, "Input ($input) should respect assigned name");
    }
  }

}
