<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Core_I18n_PseudoConstant {

  /**
   * @param $short
   *
   * @return mixed
   */
  static function longForShort($short) {
    $longForShortMapping = self::longForShortMapping();
    return $longForShortMapping[$short];
  }

  /**
   * @return array
   */
  static function &longForShortMapping() {
    static $longForShortMapping = NULL;
    if ($longForShortMapping === NULL) {
      $rows = array();
      CRM_Core_OptionValue::getValues(array('name' => 'languages'), $rows);

      $longForShortMapping = array();
      foreach ($rows as $row) {
        $longForShortMapping[$row['value']] = $row['name'];
      }
      // hand-crafted enforced overrides for language variants
      // NB: when adding support for a regional override for a new language below, update
      // relevant comments in templates/CRM/common/civicrm.settings.php.template as well
      $longForShortMapping['zh'] = defined("CIVICRM_LANGUAGE_MAPPING_ZH") ? CIVICRM_LANGUAGE_MAPPING_ZH : 'zh_CN';
      $longForShortMapping['en'] = defined("CIVICRM_LANGUAGE_MAPPING_EN") ? CIVICRM_LANGUAGE_MAPPING_EN : 'en_US';
      $longForShortMapping['fr'] = defined("CIVICRM_LANGUAGE_MAPPING_FR") ? CIVICRM_LANGUAGE_MAPPING_FR : 'fr_FR';
      $longForShortMapping['pt'] = defined("CIVICRM_LANGUAGE_MAPPING_PT") ? CIVICRM_LANGUAGE_MAPPING_PT : 'pt_PT';
      $longForShortMapping['es'] = defined("CIVICRM_LANGUAGE_MAPPING_ES") ? CIVICRM_LANGUAGE_MAPPING_ES : 'es_ES';
    }
    return $longForShortMapping;
  }

  /**
   * @param $long
   *
   * @return string
   */
  static function shortForLong($long) {
    return substr($long, 0, 2);
  }
}

