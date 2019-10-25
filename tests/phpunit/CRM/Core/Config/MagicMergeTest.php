<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CiviCRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
