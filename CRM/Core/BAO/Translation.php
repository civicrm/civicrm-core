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

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Translation;
use Civi\Core\HookInterface;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_BAO_Translation extends CRM_Core_DAO_Translation implements HookInterface {

  use CRM_Core_DynamicFKAccessTrait;

  /**
   * Get a list of valid statuses for translated-strings.
   *
   * @return array[]
   */
  public static function getStatuses(): array {
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

  /**
   * Callback for hook_civicrm_post().
   *
   * Flush out cached values.
   *
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event): void {
    unset(Civi::$statics[__CLASS__]);
  }

  /**
   * Implements hook_civicrm_apiWrappers().
   *
   * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_apiWrappers/
   *
   * @see \CRM_Utils_Hook::apiWrappers()
   * @throws \CRM_Core_Exception
   */
  public static function hook_civicrm_apiWrappers(&$wrappers, $apiRequest): void {
    if (!($apiRequest instanceof \Civi\Api4\Generic\DAOGetAction)) {
      return;
    }

    $mode = $apiRequest->getTranslationMode();
    if ($mode !== 'fuzzy') {
      return;
    }

    $communicationLanguage = \Civi\Core\Locale::detect()->nominal;
    if ($communicationLanguage === Civi::settings()->get('lcMessages')) {
      return;
    }

    if ($apiRequest['action'] === 'get') {
      if (!isset(\Civi::$statics[__CLASS__]['translate_fields'][$apiRequest['entity']][$communicationLanguage])) {
        $translated = self::getTranslatedFieldsForRequest($apiRequest);
        // @todo - once https://github.com/civicrm/civicrm-core/pull/24063 is merged
        // this could set any defined translation fields that don't have a translation
        // for one or more fields in the set to '' - ie 'if any are defined for
        // an entity/language then all must be' - it seems like being strict on this
        // now will make it easier later....
        //n No, this doesn't work - 'fields' array doesn't look like that.
        //n if (!empty($translated['fields']['msg_html']) && !isset($translated['fields']['msg_text'])) {
        //n  $translated['fields']['msg_text'] = '';
        //n }
        foreach ($translated['fields'] ?? [] as $field) {
          \Civi::$statics[__CLASS__]['translate_fields'][$apiRequest['entity']][$communicationLanguage]['fields'][$field['entity_id']][$field['entity_field']] = $field['string'];
          \Civi::$statics[__CLASS__]['translate_fields'][$apiRequest['entity']][$communicationLanguage]['language'] = $translated['language'];
        }
      }
      if (!empty(\Civi::$statics[__CLASS__]['translate_fields'][$apiRequest['entity']][$communicationLanguage])) {
        $wrappers[] = new CRM_Core_BAO_TranslateGetWrapper(\Civi::$statics[__CLASS__]['translate_fields'][$apiRequest['entity']][$communicationLanguage]);
      }
    }
  }

  /**
   * @param \Civi\Api4\Generic\AbstractAction $apiRequest
   * @return array translated fields.
   *
   * @throws \CRM_Core_Exception
   */
  protected static function getTranslatedFieldsForRequest(AbstractAction $apiRequest): array {
    $userLocale = \Civi\Core\Locale::detect();

    $translations = Translation::get()
      ->addWhere('entity_table', '=', CRM_Core_DAO_AllCoreTables::getTableForEntityName($apiRequest['entity']))
      ->setCheckPermissions(FALSE)
      ->setSelect(['entity_field', 'entity_id', 'string', 'language']);
    if ((substr($userLocale->nominal ?? '', '-3', '3') !== '_NO')) {
      // Generally we want to check for any translations of the base language
      // and prefer, for example, French French over US English for French Canadians.
      // Sites that genuinely want to cater to both will add translations for both
      // and we work through preferences below.
      // @todo: Some scripts/unit tests don't set a language so nominal is null,
      // so this then ends up retrieving all languages and then just picking
      // one. Should it treat null in a better way? Should it be an explicit
      // error not to have a language set?
      $translations->addWhere('language', 'LIKE', substr($userLocale->nominal ?? '', 0, 2) . '%');
    }
    else {
      // And here we have ... the Norwegians. They have three main variants which
      // share the same country suffix but not language prefix. As with other languages
      // any Norwegian is better than no Norwegian and sites that care will do multiple
      $translations->addWhere('language', 'LIKE', '%_NO');
    }
    $fields = $translations->execute();
    $languages = [];
    foreach ($fields as $index => $field) {
      $languages[$field['language']][$index] = $field;
    }

    $bizLocale = $userLocale->renegotiate(array_keys($languages));
    return $bizLocale
      ? ['fields' => $languages[$bizLocale->nominal], 'language' => $bizLocale->nominal]
      : [];
  }

}
