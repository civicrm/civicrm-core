<?php

/**
 * Wrapper to swap in translated text.
 */
class CRM_Core_BAO_TranslateGetWrapper {

  protected $fields;

  /**
   * Per-entity, per-field language.
   *
   * @var array
   */
  protected $translatedLanguage;

  /**
   * The languages that contributed to $fields, highest priority first - see
   * CRM_Core_BAO_Translation::getTranslatedFieldsForRequest().
   *
   * @var array
   */
  protected $priorityOrder;

  /**
   * CRM_Core_BAO_TranslateGetWrapper constructor.
   *
   * This wrapper replaces values with configured translated values, if any exist.
   *
   * @param array $translated
   */
  public function __construct($translated) {
    $this->fields = $translated['fields'];
    $this->translatedLanguage = $translated['language'];
    $this->priorityOrder = $translated['priority'];
  }

  /**
   * @inheritdoc
   */
  public function fromApiInput($apiRequest) {
    return $apiRequest;
  }

  /**
   * @inheritdoc
   */
  public function toApiOutput($apiRequest, $result) {
    foreach ($result as &$value) {
      if (!isset($value['id'], $this->fields[$value['id']])) {
        continue;
      }
      $toSet = array_intersect_key($this->fields[$value['id']], $value);
      $value = array_merge($value, $toSet);

      $entityLanguages = $this->translatedLanguage[$value['id']] ?? [];
      // Only attribute a language to fields that were actually swapped in
      $usedLanguages = array_intersect_key($entityLanguages, $toSet);
      $value['actual_language'] = $this->pickLanguage($usedLanguages);
    }
    return $result;
  }

  /**
   * Pick the "actual_language" from a set of per-field languages.
   *
   * A partially translated entity can have different fields covered by
   * different languages. Check each language in priority order, return the
   * first one that has at least one translation used here.
   *
   * @param array $languages
   *   Field name => language, for the fields actually used.
   *
   * @return string|null
   */
  protected function pickLanguage(array $languages): ?string {
    if (!$languages) {
      return NULL;
    }
    foreach ($this->priorityOrder as $language) {
      if (in_array($language, $languages, TRUE)) {
        return $language;
      }
    }
    // Fallback to any language used, though we shouldn't ever get here
    return reset($languages);
  }

}
