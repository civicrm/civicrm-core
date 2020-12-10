<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CiviCRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id: $
 *
 */

/**
 * Class CRM_Core_Config_MagicMergeTest
 * @group headless
 */
class CRM_Core_Config_MagicMergeTest extends CiviUnitTestCase {

  public function getOverrideExamples() {
    return [
      // Check examples of a few different types
      ['configAndLogDir', '/tmp/zoo'],
      ['maxAttachments', '112358'],
      ['initialized', 'for sure'],
      ['userFrameworkBaseURL', 'http://example.com/use/the/framework/luke'],
      ['inCiviCRM', 'all the data'],
      ['cleanURL', 'as clean as a url can be'],
      ['defaultCurrencySymbol', ':)'],
    ];
  }

  /**
   * @param string $field
   * @param mixed $tempValue
   * @dataProvider getOverrideExamples
   */
  public function testTempOverride($field, $tempValue) {
    $config = CRM_Core_Config::singleton();
    $origValue = $config->{$field};

    $config->{$field} = $tempValue;
    $this->assertEquals($tempValue, $config->{$field});

    $config = CRM_Core_Config::singleton();
    $this->assertEquals($tempValue, $config->{$field});

    $config = CRM_Core_Config::singleton(TRUE, TRUE);
    $this->assertEquals($origValue, $config->{$field});
  }

}
