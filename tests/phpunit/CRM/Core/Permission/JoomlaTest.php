<?php

/**
 * Class CRM_Core_Permission_JoomlaTest
 * @group headless
 */
class CRM_Core_Permission_JoomlaTest extends CiviUnitTestCase {

  /**
   * @return array
   *   (0 => input to translatePermission, 1 => expected output from translatePermission)
   */
  public function translateData() {
    $cases = [];

    $cases[] = ["administer CiviCRM", ["civicrm.administer_civicrm", "com_civicrm"]];
    // TODO $cases[] = array("cms:universal name", "local name");
    // TODO $cases[] = array("cms:universal name2", "local name2");
    $cases[] = ["cms:unknown universal name", CRM_Core_Permission::ALWAYS_DENY_PERMISSION];
    $cases[] = [
      "Joomla:civicrmplusplus.extragood:com_civicrmplusplus",
      ["civicrmplusplus.extragood", "com_civicrmplusplus"],
    ];
    $cases[] = ["otherruntime:foo", CRM_Core_Permission::ALWAYS_DENY_PERMISSION];
    $cases[] = [CRM_Core_Permission::ALWAYS_DENY_PERMISSION, CRM_Core_Permission::ALWAYS_DENY_PERMISSION];
    $cases[] = [CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION, CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION];

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
    $perm = new CRM_Core_Permission_Joomla();
    $actual = $perm->translateJoomlaPermission($input);
    $this->assertEquals($expected, $actual);
  }

}
