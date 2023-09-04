<?php

/**
 * Wrapper to swap in translated text.
 */
class CRM_Core_BAO_TranslateGetWrapper {

  protected $fields;
  protected $translatedLanguage;

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
      $value['actual_language'] = $this->translatedLanguage[$value['id']];
    }
    return $result;
  }

}
