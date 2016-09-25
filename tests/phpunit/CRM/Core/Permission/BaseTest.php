<?php

/**
 * Class CRM_Core_Permission_BaseTest
 * @group headless
 */
class CRM_Core_Permission_BaseTest extends CiviUnitTestCase {

  /**
   * @return array
   *   (0 => input to translatePermission, 1 => expected output from translatePermission)
   */
  public function translateData() {
    $cases = array();

    $cases[] = array("administer CiviCRM", "administer CiviCRM");
    $cases[] = array("cms:universal name", "local name");
    $cases[] = array("cms:universal name2", "local name2");
    $cases[] = array("cms:unknown universal name", CRM_Core_Permission::ALWAYS_DENY_PERMISSION);
    $cases[] = array("myruntime:foo", "foo");
    $cases[] = array("otherruntime:foo", CRM_Core_Permission::ALWAYS_DENY_PERMISSION);
    $cases[] = array("otherruntime:foo:bar", CRM_Core_Permission::ALWAYS_DENY_PERMISSION);
    $cases[] = array(CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION, CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION);

    return $cases;
  }

  /**
   * @dataProvider translateData
   * @param string $input
   *   The name of a permission which should be translated.
   * @param string $expected
   *   The name of an actual permission (based on translation matrix for "runtime").
   */
  public function testTranslate($input, $expected) {
    $perm = new CRM_Core_Permission_Base();
    $actual = $perm->translatePermission($input, "myruntime", array(
      'universal name' => 'local name',
      'universal name2' => 'local name2',
      'gunk' => 'gunky',
    ));
    $this->assertEquals($expected, $actual);
  }

}
