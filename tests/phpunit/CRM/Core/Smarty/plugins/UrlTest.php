<?php

/**
 * Class CRM_Core_Smarty_plugins_UrlTest
 * @group headless
 */
class CRM_Core_Smarty_plugins_UrlTest extends CiviUnitTestCase {

  /**
   * @return array
   */
  public function urlCases(): array {
    return [
      'Generate an ordinary, HTML-style URL.' => [
        'expected' => 'q=civicrm/profile/view&amp;id=123&amp;gid=456',
        'input' => '{url}//civicrm/profile/view?id=123&gid=456{/url}',
      ],
      'Here, we assign the plain-text variable and then use it for JS expression' => [
        'expected' => 'window.location = ".*q=civicrm/profile/view&id=123&gid=456"',
        'input' => '{url assign=myUrl flags=t}//civicrm/profile/view?id=123&gid=456{/url}' . 'window.location = "{$myUrl}";',
        'is_escape' => FALSE,
      ],
      'another one ...' => [
        'expected' => 'q=civicrm/profile/view&amp;id=999&amp;message=hello+world',
        'index' => '{url 1="999" 2="hello world"}//civicrm/profile/view?id=[1]&message=[2]{/url}',
      ],
      'and this one' => [
        'q=civicrm/profile/view&amp;id=123&amp;message=hello+world',
        '{url msg="hello world"}//civicrm/profile/view?id=123&message=[msg]{/url}',
      ],
      'Define a temporary variable for use in the URL.' => [
        'q=civicrm/profile/view&amp;id=123&amp;message=this+%26+that',
        '{url msg="this & that"}//civicrm/profile/view?id=123&message=[msg]{/url}',
      ],
      'We have a Smarty variable which already included escaped data. Smarty should do substitution.' => [
        'q=civicrm/profile/view&amp;id=123&amp;message=this+%2B+that',
        '{assign var=msg value="this+%2B+that"}' . '{url flags="%"}//civicrm/profile/view?id=123&message={$msg}{/url}',
      ],
      'Generate client-side route (with Angular path and params)' => [
        'expected' => 'q=civicrm/a/#/mailing/100?angularDebug=1',
        'input' => '{url id=100}backend://civicrm/a/#/mailing/[id]?angularDebug=1{/url}',
      ],
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
  }

  /**
   * @dataProvider urlCases
   *
   * @param string $expected
   * @param string $input
   * @param bool $isEscape
   *
   * @throws \CRM_Core_Exception
   */
  public function testUrl(string $expected, string $input, bool $isEscape = TRUE): void {
    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty($input);
    $regex = '!' . ($isEscape ? preg_quote($expected, '!') : $expected) . '!';
    $this->assertMatchesRegularExpression($regex, $actual, "Process input=[$input]");
  }

}
