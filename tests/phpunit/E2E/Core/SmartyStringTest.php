<?php

namespace E2E\Core;

/**
 * Evaluate the security of the Smarty configuration.
 *
 * The runs as E2E because headless tests will force-load Smarty4, which means that they don't truly
 * test other versions of Smarty.
 *
 * @package E2E\Core
 * @group e2e
 */
class SmartyStringTest extends \CiviEndToEndTestCase {

  /**
   * Test smarty syntax that should be allowed by the security policy
   *
   * @param $template
   * @param $expectedResult
   * @param array $templateVars
   *
   * @return void
   * @throws \CRM_Core_Exception
   * @dataProvider allowedSmartyCallsProvider
   */
  public function testAllowedSmartyCalls($template, $expectedResult, $templateVars = []): void {
    $this->assertEquals($expectedResult, \CRM_Utils_String::parseOneOffStringThroughSmarty($template, $templateVars));
  }

  /**
   * Test smarty syntax that should be blocked by the security policy
   *
   * @param $template
   * @param $expectedResult
   *
   * @return void
   * @throws \CRM_Core_Exception
   * @dataProvider disallowedSmartyCallProviders
   */
  public function testDisallowedSmartyCalls($template, $expectedResult): void {
    $this->expectException(\Exception::class);
    $result = \CRM_Utils_String::parseOneOffStringThroughSmarty($template);
    $this->assertNotEquals($expectedResult, $result);

    if (\CRM_Core_Smarty::singleton()->getVersion() < 3) {
      // For Smarty v2, we're just happy to know it failed -- even if it's awkwardly reported as content.
      if (preg_match(';Smarty error.*(php tags not|constants not|super global access not);', $result)) {
        throw new \Exception($result);
      }
    }
  }

  public function allowedSmartyCallsProvider(): array {
    return [
      [
        '{if count($numbers) == 2}yes{else}no{/if}',
        'yes',
        ['numbers' => [1, 2]],
      ],
      [
        '{$text|nl2br}',
        "foo<br />\nbar",
        ['text' => "foo\nbar"],
      ],
      [
        '{assign var="foo" value="bar"}{$foo|crmUpper}',
        'BAR',
      ],
    ];
  }

  public function disallowedSmartyCallProviders(): array {
    return [
      [
        "{if call_user_func(array('CRM_Utils_String', 'isAscii'), 'foo')}yes{else}no{/if}",
        'yes',
      ],
      [
        '{CRM_Core_DAO::singleValueQuery("SELECT name FROM civicrm_domain WHERE id = 1")}',
        'Default Domain Name',
      ],
      [
        '{pow(2, 4)}',
        '128',
      ],
      [
        '{php}echo "hi"{/php}',
        'hi',
      ],
      [
        // constants may include credentials, e.g. CIVICRM_DSN
        '{$smarty.const.CIVICRM_UF}',
        CIVICRM_UF,
      ],
      [
        // super-globals provide access e.g. to session cookies
        '{$smarty.server.PHP_SELF}',
        $_SERVER['PHP_SELF'],
      ],
      [
        '{crmAPI var="result" entity="Domain" action="getvalue" return="name" id=1}{$result}',
        'Default Domain Name',
      ],
    ];
  }

}
