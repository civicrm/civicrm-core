<?php

/**
 * Class CRM_Core_Permission_JoomlaTest
 */
class CRM_Core_Permission_JoomlaTest extends CiviUnitTestCase {

  /**
   * @return array
   *   (0 => input to translatePermission, 1 => expected output from translatePermission)
   */
  public function translateData() {
    $cases = array();

    $cases[] = array("administer CiviCRM", array("civicrm.administer_civicrm", "com_civicrm"));
    // TODO $cases[] = array("cms:universal name", "local name");
    // TODO $cases[] = array("cms:universal name2", "local name2");
    $cases[] = array("cms:unknown universal name", CRM_Core_Permission::ALWAYS_DENY_PERMISSION);
    $cases[] = array(
      "Joomla:civicrmplusplus.extragood:com_civicrmplusplus",
      array("civicrmplusplus.extragood", "com_civicrmplusplus"),
    );
    $cases[] = array("otherruntime:foo", CRM_Core_Permission::ALWAYS_DENY_PERMISSION);
    $cases[] = array(CRM_Core_Permission::ALWAYS_DENY_PERMISSION, CRM_Core_Permission::ALWAYS_DENY_PERMISSION);
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
    $perm = new CRM_Core_Permission_Joomla();
    $actual = $perm->translateJoomlaPermission($input);
    $this->assertEquals($expected, $actual);
  }

}
