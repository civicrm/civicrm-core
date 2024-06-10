<?php

/**
 * Class CRM_Core_Smarty_plugins_CrmMoneyTest
 * @group headless
 * @group locale
 */
class CRM_Core_Smarty_plugins_CrmPermissionTest extends CiviUnitTestCase {

  /**
   * @dataProvider permissionCases
   *
   * @param string $input
   *
   * @param string $expected
   * @param bool $isAdmin
   *
   * @throws \CRM_Core_Exception
   */
  public function testPermission(string $input, string $expected, bool $isAdmin) {
    if ($isAdmin) {
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
        'access CiviCRM',
        'administer CiviCRM',
      ];
    }
    else {
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
        'access CiviCRM',
      ];
    }
    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty($input);
    $this->assertEquals($expected, $actual, "Process input=[$input]");
  }

  /**
   * Get variations on permission configuration.
   *
   * @return array[]
   */
  public function permissionCases(): array {
    return [
      'has_allowed' => ['{crmPermission has="administer CiviCRM"}boom{/crmPermission}', 'boom', TRUE],
      'has_blocked' => ['{crmPermission has="administer CiviCRM"}boom{/crmPermission}', '', FALSE],
      'has_multiple_perm_allowed' => ['{crmPermission has="access CiviCRM,administer CiviCRM"}boom{/crmPermission}', 'boom', TRUE],
      'not_has_allowed' => ['{crmPermission not="administer CiviCRM"}boom{/crmPermission}', '', TRUE],
      'not_has_blocked' => ['{crmPermission not="administer CiviCRM"}boom{/crmPermission}', 'boom', FALSE],
      'not_multiple_perm_blocked' => ['{crmPermission not="access CiviEvent,access CiviMember"}boom{/crmPermission}', 'boom', FALSE],
      'has_and_not_allowed' => ['{crmPermission has="access CiviCRM" not="administer CiviCRM"}boom{/crmPermission}', 'boom', FALSE],
      'has_and_not_blocked' => ['{crmPermission has="access CiviCRM" not="administer CiviCRM"}boom{/crmPermission}', '', TRUE],
      'has_not_and_has_allowed' => ['{crmPermission has="administer CiviCRM" not="access CiviEvent"}boom{/crmPermission}', 'boom', TRUE],
      'has_not_and_has_blocked' => ['{crmPermission has="administer CiviCRM" not="access CiviEvent"}boom{/crmPermission}', '', FALSE],
    ];
  }

}
