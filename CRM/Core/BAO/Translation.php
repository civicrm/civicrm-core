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
class CRM_Core_BAO_Translation extends CRM_Core_DAO_Translation {

  /**
   * Get a list of valid statuses for translated-strings.
   *
   * @return string[]
   */
  public static function getStatuses($context = NULL) {
    $options = [
      ['id' => 1, 'name' => 'active', 'label' => ts('Active')],
      ['id' => 2, 'name' => 'draft', 'label' => ts('Draft')],
    ];
    return self::formatPsuedoconstant($context, $options);
  }

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
   * Given a constant list of of id/name/label options, convert to the
   * format required by pseudoconstant.
   *
   * @param string|NULL $context
   * @param array $options
   *   List of options, each as a record of id+name+label.
   *   Ex: [['id' => 123, 'name' => 'foo_bar', 'label' => 'Foo Bar']]
   *
   * @return array|false
   */
  private static function formatPsuedoconstant($context, array $options) {
    // https://docs.civicrm.org/dev/en/latest/framework/pseudoconstant/#context
    $key = ($context === 'match') ? 'name' : 'id';
    $value = ($context === 'validate') ? 'name' : 'label';
    return array_combine(array_column($options, $key), array_column($options, $value));
  }

  /**
   * @return array
   *   List of data fields to translate, organized by table and column.
   *   Omitted/unlisted fields are not translated. Any listed field may be translated.
   *   Values should be TRUE.
   *   Ex: $fields['civicrm_event']['summary'] = TRUE
   */
  public static function getTranslatedFields() {
    $key = 'translatedFields';
    $cache = Civi::cache('fields');
    if (($r = $cache->get($key)) !== NULL) {
      return $r;
    }

    $f = [];
    \CRM_Utils_Hook::translateFields($f);

    // Future: Assimilate defaults originating in XML (incl extension-entities)
    // e.g. CRM_Core_I18n_SchemaStructure::columns() will grab core fields

    $cache->set($key, $f);
    return $f;
  }

}
