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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_I18n_PseudoConstant {

  /**
   * @param $short
   *
   * @return mixed
   */
  public static function longForShort($short) {
    $longForShortMapping = self::longForShortMapping();
    return $longForShortMapping[$short];
  }

  /**
   * @return array
   */
  public static function &longForShortMapping() {
    static $longForShortMapping = NULL;
    if ($longForShortMapping === NULL) {
      $rows = [];
      CRM_Core_OptionValue::getValues(['name' => 'languages'], $rows);

      $longForShortMapping = [];
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
      $longForShortMapping['nl'] = defined("CIVICRM_LANGUAGE_MAPPING_NL") ? CIVICRM_LANGUAGE_MAPPING_NL : 'nl_NL';
    }
    return $longForShortMapping;
  }

  /**
   * @param string $long
   *
   * @return string
   */
  public static function shortForLong($long) {
    return substr($long, 0, 2);
  }

  /**
   * Returns a list of ISO 639-1 "right-to-left" language codes.
   *
   * @return array
   */
  public static function getRTLlanguages() {
    $rtl = [
      'ar',
      'fa',
      'he',
      'ur',
    ];

    return $rtl;
  }

}
