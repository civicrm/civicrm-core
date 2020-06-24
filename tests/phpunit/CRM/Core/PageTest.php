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
  public function iconTestData() {
    // first item is icon, text, condition, and attribs, second is expected markup
    return [
      [
        '<i aria-hidden="true" title="We have a winner" class="crm-i fa-trophy"></i><span class="sr-only">We have a winner</span>',
        ['fa-trophy', 'We have a winner', TRUE, []],
        '{icon icon="fa-trophy"}We have a winner{/icon}',
      ],
      [
        '',
        ['fa-trophy', 'We have a winner', 0, []],
        '{icon icon="fa-trophy" condition=0}We have a winner{/icon}',
      ],
      [
        '<i aria-hidden="true" title="Favorite" class="action-icon test-icon crm-i fa-heart"></i><span class="sr-only">Favorite</span>',
        ['fa-heart', 'Favorite', TRUE, ['class' => 'action-icon test-icon']],
        '{icon icon="fa-heart" class="action-icon test-icon"}Favorite{/icon}',
      ],
      [
        '<i aria-hidden="true" title="I &quot;choo-choo&quot; choose you" class="crm-i fa-train"></i><span class="sr-only">I "choo-choo" choose you</span>',
        ['fa-train', 'I "choo-choo" choose you', TRUE, []],
        '{icon icon="fa-train"}I "choo-choo" choose you{/icon}',
      ],
      [
        '<i aria-hidden="true" class="crm-i fa-trash"></i><span class="sr-only">Trash</span>',
        ['fa-trash', 'Trash', TRUE, ['title' => '']],
        '{icon icon="fa-trash" title=""}Trash{/icon}',
      ],
      [
        '<i title="It\'s bedtime" class="crm-i fa-bed"></i><span class="sr-only">It\'s bedtime</span>',
        ['fa-bed', "It's bedtime", TRUE, ['aria-hidden' => '']],
        // Ye olde Smarty 2 doesn't support hyphenated function parameters
      ],
      [
        '<i aria-hidden="true" class="crm-i fa-snowflake-o"></i>',
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
      $smarty = CRM_Core_Smarty::singleton();
      $actual = $smarty->fetch('string:' . $smartyFunc);
      $this->assertEquals($expectedMarkup, $actual, "Process input=[$smartyFunc]");
    }
  }

}
