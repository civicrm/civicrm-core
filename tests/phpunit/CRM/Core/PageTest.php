<?php

/**
 * Class CRM_Core_PageTest
 * @group headless
 */
class CRM_Core_PageTest extends CiviUnitTestCase {

  /**
   * Test data for testMakeIcons
   *
   * @return array
   */
  public static function iconTestData() {
    // First item is expected markup, second is php params and third is equivalent smarty syntax for the same params
    return [
      [
        '<i role="img" aria-hidden="true" title="Test 1 &amp; 2" class="crm-i fa-trophy"></i><span class="sr-only">Test 1 &amp; 2</span>',
        ['fa-trophy', 'Test 1 & 2', TRUE, []],
        '{icon icon="fa-trophy"}Test 1 & 2{/icon}',
      ],
      [
        '',
        ['fa-trophy', 'We have a winner', 0, []],
        '{icon icon="fa-trophy" condition=0}We have a winner{/icon}',
      ],
      [
        '<i role="img" aria-hidden="true" title="Favorite &lt;3" class="action-icon test-icon crm-i fa-heart"></i><span class="sr-only">Favorite &lt;3</span>',
        ['fa-heart', 'Favorite <3', TRUE, ['class' => 'action-icon test-icon']],
        '{icon icon="fa-heart" class="action-icon test-icon"}Favorite <3{/icon}',
      ],
      [
        '<i role="img" aria-hidden="true" title="I &quot;choo-choo&quot; choose you" class="crm-i fa-train"></i><span class="sr-only">I "choo-choo" choose you</span>',
        ['fa-train', 'I "choo-choo" choose you', TRUE, []],
        '{icon icon="fa-train"}I "choo-choo" choose you{/icon}',
      ],
      [
        '<i role="img" aria-hidden="true" class="crm-i fa-trash"></i><span class="sr-only">Trash</span>',
        ['fa-trash', 'Trash', TRUE, ['title' => '']],
        '{icon icon="fa-trash" title=""}Trash{/icon}',
      ],
      [
        '<i role="img" title="It\'s bedtime" class="crm-i fa-bed"></i><span class="sr-only">It\'s bedtime</span>',
        ['fa-bed', "It's bedtime", TRUE, ['aria-hidden' => '']],
        // Ye olde Smarty 2 doesn't support hyphenated function parameters
      ],
      [
        '<i role="img" aria-hidden="true" class="crm-i fa-snowflake-o"></i>',
        ['fa-snowflake-o', NULL, TRUE, []],
        '{icon icon="fa-snowflake-o"}{/icon}',
      ],
    ];
  }

  /**
   * Test that icons are formed properly
   *
   * @param string $expectedMarkup
   * @param array $params
   * @param string $smartyFunc
   * @dataProvider iconTestData
   */
  public function testMakeIcons($expectedMarkup, $params, $smartyFunc = '') {
    list($icon, $text, $condition, $attribs) = $params;
    $this->assertEquals($expectedMarkup, CRM_Core_Page::crmIcon($icon, $text, $condition, $attribs));
    if (!empty($smartyFunc)) {
      $actual = CRM_Utils_String::parseOneOffStringThroughSmarty($smartyFunc);
      $this->assertEquals($expectedMarkup, $actual, "Process input=[$smartyFunc]");
    }
  }

}
