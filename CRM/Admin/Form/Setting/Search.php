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

/**
 * This class generates form components for Search Parameters
 *
 */
class CRM_Admin_Form_Setting_Search extends CRM_Admin_Form_Generic {

  /**
   * Define sections.
   */
  public function preProcess(): void {
    parent::preProcess();
    $this->sections = [
      'search' => [
        'title' => ts('Search Configuration'),
        'icon' => 'fa-search',
        'weight' => 0,
      ],
      'autocomplete' => [
        'title' => ts('Autocompletes'),
        'icon' => 'fa-keyboard',
        'weight' => 10,
      ],
      'legacy' => [
        'title' => ts('Legacy Search Settings'),
        'description' => ts('These settings do not apply to the new SearchKit search engine.'),
        'icon' => 'fa-clock-rotate-left',
        'weight' => 50,
      ],
    ];
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    // Option 1 can't be unchecked. @see self::enableOptionOne
    $element = $this->getElement('contact_autocomplete_options');
    $element->_elements[0]->setAttribute('disabled', 'disabled');

    // Option 1 can't be unchecked. @see self::enableOptionOne
    $element = $this->getElement('contact_reference_options');
    $element->_elements[0]->setAttribute('disabled', 'disabled');
  }

  /**
   * @return array
   */
  public static function getContactAutocompleteOptions() {
    return [1 => ts('Contact Name')] + CRM_Core_OptionGroup::values('contact_autocomplete_options', FALSE, FALSE, TRUE);
  }

  /**
   * @return array
   */
  public static function getAvailableProfiles() {
    return ['' => ts('- none -')] + CRM_Core_BAO_UFGroup::getProfiles([
      'Contact',
      'Individual',
      'Organization',
      'Household',
    ]);
  }

  /**
   * @return array
   */
  public static function getContactReferenceOptions() {
    return [1 => ts('Contact Name')] + CRM_Core_OptionGroup::values('contact_reference_options', FALSE, FALSE, TRUE);
  }

  /**
   * Presave callback for contact_reference_options and contact_autocomplete_options.
   *
   * Ensures "1" is always contained in the array.
   *
   * @param $value
   * @return bool
   */
  public static function enableOptionOne(&$value) {
    $values = (array) CRM_Utils_Array::explodePadded($value);
    if (!in_array(1, $values)) {
      $value = CRM_Utils_Array::implodePadded(array_merge([1], $values));
    }
    return TRUE;
  }

  /**
   * Pseudoconstant callback for autocomplete_displays setting.
   *
   * @return array
   */
  public static function getAutocompleteDisplays(): array {
    $options = [];
    try {
      $displays = civicrm_api4('SearchDisplay', 'get', [
        'checkPermissions' => FALSE,
        'select' => ['name', 'saved_search_id.api_entity', 'label'],
        'where' => [
          ['type', '=', 'autocomplete'],
        ],
        'orderBy' => [
          'saved_search_id.api_entity' => 'ASC',
          'label' => 'ASC',
        ],
      ]);
      foreach ($displays as $display) {
        $entityLabel = \Civi\Api4\Utils\CoreUtil::getInfoItem($display['saved_search_id.api_entity'], 'title_plural');
        $options[$display['saved_search_id.api_entity'] . ':' . $display['name']] = $entityLabel . ': ' . $display['label'];
      }
    }
    catch (CRM_Core_Exception $e) {
      // Catch added in case of early bootstrap situations where SearchKit extension is not loaded.
    }
    return $options;
  }

  /**
   * post_change callback for autocomplete_displays setting.
   *
   * Ensures the settings only allow one display per entity and
   * verifies the mapped displays exist.
   *
   * @param array $oldValue
   * @param array $newValue
   *
   * @return void
   */
  public static function onChangeAutocompleteDisplays($oldValue, $newValue): void {
    if (!$newValue) {
      return;
    }
    $mappedValue = [];
    // Explode "key:value"[] into [key => value]
    // This effectively enforces a max of one display per entity, (more than one wouldn't make any sense)
    foreach ($newValue as $setting) {
      [$entityName, $displayName] = explode(':', $setting);
      $mappedValue[$entityName] = $displayName;
    }
    try {
      // Validate that displays exist and are paired with the correct entity
      // Any mismatches or missing displays will be rejected
      $mappedDisplays = civicrm_api4('SearchDisplay', 'get', [
        'checkPermissions' => FALSE,
        'select' => ['name', 'saved_search_id.api_entity'],
        'where' => [
          ['type', '=', 'autocomplete'],
          ['name', 'IN', array_values($mappedValue)],
        ],
      ])->column('name', 'saved_search_id.api_entity');
      $mappedValue = array_intersect_assoc($mappedValue, $mappedDisplays);
    }
    catch (CRM_Core_Exception $e) {
      // Catch added in case of early bootstrap situations where SearchKit extension is not loaded.
    }
    // Reformat as "key:value"[] before storing setting
    $value = array_map(
      fn($mappedValue, $mappedKey) => $mappedKey . ':' . $mappedValue,
      $mappedValue,
      array_keys($mappedValue)
    );
    // Re-save setting. This shouldn't cause an infinite loop because the second time this condition will be false.
    if ($newValue != $value) {
      Civi::settings()->set('autocomplete_displays', $value);
    }
  }

}
