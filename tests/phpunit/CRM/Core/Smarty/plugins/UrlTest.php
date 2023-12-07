<?php

/**
 * Class CRM_Core_Smarty_plugins_UrlTest
 * @group headless
 */
class CRM_Core_Smarty_plugins_UrlTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    require_once 'CRM/Core/Smarty.php';

    // Templates should normally be file names, but for unit-testing it's handy to use "string:" notation
    require_once 'CRM/Core/Smarty/resources/String.php';
    civicrm_smarty_register_string_resource();

    $this->useTransaction();
  }

  /**
   * @return array
   */
  public function urlCases() {
    $literal = function(string $s) {
      return '!' . preg_quote($s, '!') . '!';
    };

    $cases = [];
    $cases[] = [
      // Generate an ordinary, HTML-style URL.
      $literal('q=civicrm/profile/view&amp;id=123&amp;gid=456'),
      '{url}//civicrm/profile/view?id=123&gid=456{/url}',
    ];
    $cases[] = [
      // Here, we assign the plain-text variable and then use it for JS expression
      '!window.location = ".*q=civicrm/profile/view&id=123&gid=456"!',
      '{url assign=myUrl flags=t}//civicrm/profile/view?id=123&gid=456{/url}' .
      'window.location = "{$myUrl}";',
    ];
    $cases[] = [
      $literal('q=civicrm/profile/view&amp;id=999&amp;message=hello+world'),
      '{url 1="999" 2="hello world"}//civicrm/profile/view?id=[1]&message=[2]{/url}',
    ];
    $cases[] = [
      $literal('q=civicrm/profile/view&amp;id=123&amp;message=hello+world'),
      '{url msg="hello world"}//civicrm/profile/view?id=123&message=[msg]{/url}',
    ];
    $cases[] = [
      // Define a temporary variable for use in the URL.
      $literal('q=civicrm/profile/view&amp;id=123&amp;message=this+%26+that'),
      '{url msg="this & that"}//civicrm/profile/view?id=123&message=[msg]{/url}',
    ];
    $cases[] = [
      // We have a Smarty variable which already included escaped data. Smarty should do substitution.
      $literal('q=civicrm/profile/view&amp;id=123&amp;message=this+%2B+that'),
      '{assign var=msg value="this+%2B+that"}' .
      '{url flags=%}//civicrm/profile/view?id=123&message={$msg}{/url}',
    ];
    $cases[] = [
      // Generate client-side route (with Angular path and params)
      $literal('q=civicrm/a/#/mailing/100?angularDebug=1'),
      '{url id=100}backend://civicrm/a/#/mailing/[id]?angularDebug=1{/url}',
    ];

    // This example is neat - you just replace `{$msg}` with `[msg]`, and then you get encoded URL data.
    // But... it's pretty shallow. You can't use Smarty expressions or modifiers. Additionally,
    // enabling this mode increases the risk of accidental collisions between Smarty variables
    // and deep-form-params. So I've left it disabled for now.
    //
    // $cases[] = [
    //   // We have a Smarty variable with canonical (unescaped) data. Use it as URL variable.
    //   $literal('q=civicrm/profile/view&amp;id=123&amp;message=this+%2B+that'),
    //   '{assign var=msg value="this + that"}' .
    //   '{url}//civicrm/profile/view?id=123&message=[msg]{/url}',
    // ];

    // return CRM_Utils_Array::subset($cases, [2]);
    return $cases;
  }

  /**
   * @dataProvider urlCases
   * @param string $expected
   * @param string $input
   */
  public function testUrl($expected, $input) {
    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:' . $input);
    $this->assertMatchesRegularExpression($expected, $actual, "Process input=[$input]");
  }

}
