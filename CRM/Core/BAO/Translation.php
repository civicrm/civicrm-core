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
class CRM_Core_BAO_Translation extends CRM_Core_DAO_Translation implements \Civi\Core\HookInterface {

  use CRM_Core_DynamicFKAccessTrait;

  /**
   * Get a list of valid statuses for translated-strings.
   *
   * @return array[]
   */
  public static function getStatuses() {
    return [
      ['id' => 1, 'name' => 'active', 'label' => ts('Active')],
      ['id' => 2, 'name' => 'draft', 'label' => ts('Draft')],
    ];
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

  /**
   * When manipulating strings via the `Translation` entity (APIv4), ensure that the references are well-formed.
   *
   * @param \Civi\Api4\Event\ValidateValuesEvent $e
   */
  public static function self_civi_api4_validate(\Civi\Api4\Event\ValidateValuesEvent $e) {
    $statuses = array_column(self::getStatuses(), 'id');
    $dataTypes = [CRM_Utils_Type::T_STRING, CRM_Utils_Type::T_TEXT, CRM_Utils_Type::T_LONGTEXT];
    $htmlTypes = ['Text', 'TextArea', 'RichTextEditor', ''];

    foreach ($e->records as $r => $record) {
      if (array_key_exists('status_id', $record) && !in_array($record['status_id'], $statuses)) {
        $e->addError($r, 'status_id', 'invalid', ts('Invalid status'));
      }

      $entityIdFields = ['entity_table', 'entity_field', 'entity_id'];
      $entityIdCount = (empty($record['entity_table']) ? 0 : 1)
        + (empty($record['entity_field']) ? 0 : 1)
        + (empty($record['entity_id']) ? 0 : 1);
      if ($entityIdCount === 0) {
        continue;
      }
      elseif ($entityIdCount < 3) {
        $e->addError($r, $entityIdFields, 'full_entity', ts('Must specify all entity identification fields'));
      }

      $simpleName = '/^[a-zA-Z0-9_]+$/';
      if (!preg_match($simpleName, $record['entity_table']) || !preg_match($simpleName, $record['entity_field']) || !is_numeric($record['entity_id'])) {
        $e->addError($r, $entityIdFields, 'malformed_entity', ts('Entity reference is malformed'));
        continue;
      }

      // Which fields support translation?
      // - One could follow the same path as "Multilingual". Use
      //   $translatable = CRM_Core_I18n_SchemaStructure::columns();
      //   if (!isset($translatable[$record['entity_table']][$record['entity_field']])) {
      // - Or, since we don't need schema-changes, we could be more generous and allow all freeform text fields...

      $daoClass = CRM_Core_DAO_AllCoreTables::getClassForTable($record['entity_table']);
      if (!$daoClass) {
        $e->addError($r, 'entity_table', 'bad_table', ts('Entity reference specifies a non-existent or non-translatable table'));
        continue;
      }

      $dao = new $daoClass();
      $dao->id = $record['entity_id'];

      $field = $dao->getFieldSpec($record['entity_field']);
      if (!$field || !in_array($field['type'] ?? '', $dataTypes) || !in_array($field['html']['type'] ?? '', $htmlTypes)) {
        $e->addError($r, 'entity_field', 'bad_field', ts('Entity reference specifies a non-existent or non-translatable field'));
      }
      if (!$dao->find()) {
        $e->addError($r, 'entity_id', 'nonexistent_id', ts('Entity does not exist'));
      }
    }
  }

}
