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

use Civi\Core\HookInterface;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_BAO_TranslationSource extends CRM_Core_DAO_TranslationSource implements HookInterface {

  /**
   * Get a list of tables with translatable strings.
   *
   * @return string[]
   *   Ex: ['civicrm_event' => 'civicrm_event']
   */
  public static function getEntityTables() {
    if (!isset(Civi::$statics[__CLASS__]['allTables'])) {
      $tables = array_keys(self::getTranslatedFields());
      Civi::$statics[__CLASS__]['allTables'] = array_combine($tables, $tables);
    }
    return Civi::$statics[__CLASS__]['allTables'];
  }

  /**
   * Get a list of fields with translatable strings.
   *
   * @return string[]
   *   Ex: ['title' => 'title', 'description' => 'description']
   */
  public static function getEntityFields() {
    if (!isset(Civi::$statics[__CLASS__]['allFields'])) {
      $allFields = [];
      foreach (self::getTranslatedFields() as $columns) {
        foreach ($columns as $column => $sqlExpr) {
          $allFields[$column] = $column;
        }
      }
      Civi::$statics[__CLASS__]['allFields'] = $allFields;
    }
    return Civi::$statics[__CLASS__]['allFields'];
  }

  /**
   * Mark these fields as translatable.
   *
   * @todo move this definition to the metadata.
   *
   * @see CRM_Utils_Hook::translateFields
   */
  public static function hook_civicrm_translateFields(&$fields) {
    $fields['civicrm_translation_source']['source'] = TRUE;
  }

  /**
   * Get all the translations sources replacement
   */
  public static function getTranslationSources($language) {
    $sources = [];
    // FIXME: filter by lang
    $sql = "
SELECT source, string
FROM civicrm_translation t
  INNER JOIN civicrm_translation_source ts ON t.entity_table = 'civicrm_translation_source' AND t.entity_field = 'source' AND t.entity_id = ts.id
WHERE t.language = %1 AND t.status_id = 1";
    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$language, 'String']], TRUE, NULL, FALSE, FALSE);
    while ($dao->fetch()) {
      $sources[$dao->source] = $dao->string;
    }
    return $sources;
  }

}
