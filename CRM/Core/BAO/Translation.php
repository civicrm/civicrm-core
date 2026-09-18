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
    if (!($apiRequest instanceof \Civi\Api4\Generic\DAOGetAction) || $apiRequest['action'] !== 'get') {
      return;
    }

    if ($apiRequest->getTranslationMode() !== 'fuzzy') {
      return;
    }

    $communicationLanguage = \Civi\Core\Locale::detect()->nominal;
    if (!$communicationLanguage || !self::isTranslate($communicationLanguage)) {
      return;
    }

    $cache = &\Civi::$statics[__CLASS__]['translate_fields'][$apiRequest['entity']][$communicationLanguage];
    if (!isset($cache)) {
      $translated = self::getTranslatedFieldsForRequest($apiRequest);
      $cache['priority'] = $translated['priority'];
      foreach ($translated['fields'] as $field) {
        $cache['fields'][$field['entity_id']][$field['entity_field']] = $field['string'];
        // Record the language per field. Which language counts as the language for
        // the entity is then decided per-request, based on which fields actually
        // get used - see CRM_Core_BAO_TranslateGetWrapper.
        $cache['language'][$field['entity_id']][$field['entity_field']] = $field['language'];
      }
    }
    if (!empty($cache['fields'])) {
      $wrappers[] = new CRM_Core_BAO_TranslateGetWrapper($cache);
    }
  }

  /**
   * Should the translation process be followed.
   *
   * It can be short-circuited if there we are in the site default language and
   * it is not translated.
   *
   * @param string $communicationLanguage
   *
   * @return bool
   */
  protected static function isTranslate(string $communicationLanguage): bool {
    if ($communicationLanguage !== Civi::settings()->get('lcMessages')) {
      return TRUE;
    }
    if (!isset(\Civi::$statics[__CLASS__]['translate_main'][$communicationLanguage])) {
      // The code had an assumption that you would not translate the primary language.
      // However, the UI is such that the features (approval flow) so it makes sense
      // to translation the default site language as well. If we can see sites are
      // doing this then let's treat the main locale like any other locale
      \Civi::$statics[__CLASS__]['translate_main'] = (bool) CRM_Core_DAO::singleValueQuery(
        'SELECT COUNT(*) FROM civicrm_translation WHERE language = %1 LIMIT 1', [
          1 => [$communicationLanguage, 'String'],
        ]
      );
    }
    return \Civi::$statics[__CLASS__]['translate_main'];
  }

  /**
   * @param \Civi\Api4\Generic\AbstractAction $apiRequest
   * @return array
   *   [
   *     'fields' => translated fields, keyed by entity_id . entity_field,
   *     'priority' => the languages used in 'fields', highest priority first.
   *   ]
   *
   * @throws \CRM_Core_Exception
   */
  protected static function getTranslatedFieldsForRequest(AbstractAction $apiRequest): array {
    $userLocale = \Civi\Core\Locale::detect();

    $translations = Translation::get()
      ->addWhere('entity_table', '=', CRM_Core_DAO_AllCoreTables::getTableForEntityName($apiRequest['entity']))
      ->addWhere('status_id:name', '=', 'active')
      ->setCheckPermissions(FALSE)
      ->setSelect(['entity_field', 'entity_id', 'string', 'language']);
    if (!self::isNorwegianLocale($userLocale->nominal)) {
      // Generally we want to check for any translations of the base language
      // and prefer, for example, French French over US English for French Canadians.
      // Sites that genuinely want to cater to both will add translations for both
      // and we work through preferences below.
      // @todo: Some scripts/unit tests don't set a language so nominal is null,
      // so this then ends up retrieving all languages and then just picking
      // one. Should it treat null in a better way? Should it be an explicit
      // error not to have a language set?
      $languageCondition = ['language', 'LIKE', substr($userLocale->nominal ?? '', 0, 2) . '%'];
    }
    else {
      // And here we have ... the Norwegians. They have three main variants which
      // share the same country suffix but not language prefix. As with other languages
      // any Norwegian is better than no Norwegian and sites that care will do multiple.
      $languageCondition = ['language', 'LIKE', '%_NO'];
    }
    // Also fetch the site-default language in the same query, in case there is no translation.
    $siteDefaultLanguage = \Civi::settings()->get('lcMessages');
    $translations->addClause('OR', $languageCondition, ['language', '=', $siteDefaultLanguage]);
    $rows = $translations->execute();
    $languages = [];
    foreach ($rows as $row) {
      $languages[$row['language']][$row['entity_id'] . $row['entity_field']] = $row;
    }

    $bizLocale = $userLocale->renegotiate(array_keys($languages));
    $negotiatedLanguage = $bizLocale?->nominal;
    $otherLanguages = array_diff(array_keys($languages), [$siteDefaultLanguage, $negotiatedLanguage]);
    // Order the other languages by precedence order.
    $fallbackOrder = array_reverse(\Civi\Core\Locale::getAllFallbacks($userLocale->nominal));
    $otherLanguages = [
      ...array_diff($otherLanguages, $fallbackOrder),
      ...array_intersect($fallbackOrder, $otherLanguages),
    ];
    // Lowest priority first: site default, then fetched variants, then -
    // unless it's just the site default reached via fallback - the
    // negotiated language itself.
    if ($negotiatedLanguage !== NULL && $negotiatedLanguage !== $siteDefaultLanguage) {
      $priorityOrder = [$siteDefaultLanguage, ...$otherLanguages, $negotiatedLanguage];
    }
    // If the negotiated language is in the same family as the userLocale
    // language, then this isn't a fallback, it's either the site default or
    // a substitution that we want to give priority (e.g. es_ES for es_MX).
    elseif ($negotiatedLanguage !== NULL && self::isSameFamily($userLocale->nominal, $negotiatedLanguage)) {
      $priorityOrder = [...$otherLanguages, $siteDefaultLanguage];
    }
    // Requested language was in a different family than what we ended up
    // with (or we ended up with null) after negotiation, so the other
    // languages are priority.
    else {
      $priorityOrder = [$siteDefaultLanguage, ...$otherLanguages];
    }

    $fields = [];
    foreach ($priorityOrder as $language) {
      $fields = array_merge($fields, $languages[$language] ?? []);
    }
    return ['fields' => $fields, 'priority' => array_reverse($priorityOrder)];
  }

  /**
   * Is this a Norwegian locale?
   *
   * Norwegian's variants (nb_NO, nn_NO, ...) share a country suffix rather
   * than a language prefix, so they need special handling.
   *
   * @param string|null $locale
   *
   * @return bool
   */
  private static function isNorwegianLocale(?string $locale): bool {
    return substr($locale ?? '', -3, 3) === '_NO';
  }

  /**
   * Do these two locales belong to the same language family, i.e. the same
   * language prefix (e.g. 'es_MX' and 'es_ES') — or the same country suffix
   * (e.g. 'nb_NO' and 'nn_NO') for Norwegian?
   *
   * @param string|null $localeA
   * @param string|null $localeB
   *
   * @return bool
   */
  private static function isSameFamily(?string $localeA, ?string $localeB): bool {
    if (self::isNorwegianLocale($localeA)) {
      return self::isNorwegianLocale($localeB);
    }
    return substr($localeA ?? '', 0, 2) === substr($localeB ?? '', 0, 2);
  }

}
