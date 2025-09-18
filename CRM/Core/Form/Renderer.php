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

require_once 'HTML/QuickForm/Renderer/ArraySmarty.php';

/**
 * Customize QF output to meet our specific requirements
 */
class CRM_Core_Form_Renderer extends HTML_QuickForm_Renderer_ArraySmarty {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   */
  static private $_singleton = NULL;

  /**
   * The converter from array size to css class.
   *
   * @var array
   */
  public static $_sizeMapper = [
    2 => 'two',
    4 => 'four',
    6 => 'six',
    8 => 'eight',
    12 => 'twelve',
    20 => 'medium',
    30 => 'big',
    45 => 'huge',
  ];

  /**
   * Constructor.
   */
  public function __construct() {
    $template = CRM_Core_Smarty::singleton();
    parent::__construct($template);
  }

  /**
   * Static instance provider.
   *
   * Method providing static instance of as in Singleton pattern.
   */
  public static function &singleton() {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_Core_Form_Renderer();
    }
    return self::$_singleton;
  }

  /**
   * Creates an array representing an element containing.
   * the key for storing this. We allow the parent to do most of the
   * work, but then we add some CiviCRM specific enhancements to
   * make the html compliant with our css etc
   *
   *
   * @param HTML_QuickForm_element $element
   * @param bool $required
   *   Whether an element is required.
   * @param string $error
   *   Error associated with the element.
   *
   * @return array
   */
  public function _elementToArray(&$element, $required, $error) {
    self::updateAttributes($element, $required, $error);

    $el = parent::_elementToArray($element, $required, $error);
    $el['textLabel'] = $element->_label ?? NULL;

    // add label html
    if (!empty($el['label'])) {
      $id = $element->getAttribute('id');
      if (!empty($id)) {
        $el['label'] = '<label for="' . $id . '">' . $el['label'] . '</label>';
      }
      else {
        $el['label'] = "<label>{$el['label']}</label>";
      }
    }

    // Display-only (frozen) elements
    if (!empty($el['frozen'])) {
      if ($element->getAttribute('data-api-entity') && $element->getAttribute('data-entity-value')) {
        $this->renderFrozenEntityRef($el, $element);
      }
      elseif ($element->getAttribute('type') == 'text' && $element->getAttribute('data-select-params')) {
        $this->renderFrozenSelect2($el, $element);
      }
      elseif ($element->getAttribute('type') == 'text' && $element->getAttribute('data-crm-datepicker')) {
        $this->renderFrozenDatepicker($el, $element);
      }
      elseif ($element->getAttribute('type') == 'text' && $element->getAttribute('formatType')) {
        [$date, $time] = CRM_Utils_Date::setDateDefaults($element->getValue(), $element->getAttribute('formatType'), $element->getAttribute('format'), $element->getAttribute('timeformat'));
        $date .= ($element->getAttribute('timeformat')) ? " $time" : '';
        $el['html'] = $date . '<input type="hidden" value="' . $element->getValue() . '" name="' . $element->getAttribute('name') . '">';
      }
      // Render html for wysiwyg textareas
      if ($el['type'] == 'textarea' && isset($element->_attributes['class']) && str_contains($element->_attributes['class'], 'wysiwyg')) {
        $el['html'] = '<span class="crm-frozen-field">' . $el['value'] . '</span>';
      }
      else {
        $el['html'] = '<span class="crm-frozen-field">' . $el['html'] . '</span>';
      }
    }
    // Active form elements
    else {
      $typesToShowEditLink = ['select', 'group'];
      $hasEditPath = NULL !== $element->getAttribute('data-option-edit-path');

      if (in_array($element->getType(), $typesToShowEditLink) && $hasEditPath) {
        $this->addOptionsEditLink($el, $element);
      }

      if ($element->getAttribute('allowClear')) {
        $this->appendUnselectButton($el, $element);
      }
    }

    return $el;
  }

  /**
   * Update the attributes of this element and add a few CiviCRM
   * based attributes so we can style this form element better
   *
   *
   * @param HTML_QuickForm_element $element
   * @param bool $required
   *   Whether an element is required.
   * @param string $error
   *   Error associated with the element.
   *
   */
  public static function updateAttributes(&$element, $required, $error) {
    // lets create an id for all input elements, so we can generate nice label tags
    // to make it nice and clean, we'll just use the elementName if it is non null
    $attributes = [];
    if (!$element->getAttribute('id')) {
      $name = $element->getAttribute('name');
      if ($name) {
        $attributes['id'] = str_replace([']', '['],
          ['', '_'],
          $name
        );
      }
    }

    $class = $element->getAttribute('class') ?? '';
    $type = $element->getType();
    if (!$class) {
      if ($type == 'text' || $type == 'password') {
        $size = $element->getAttribute('size');
        if (!empty($size)) {
          $class = self::$_sizeMapper[$size] ?? '';
        }
      }
    }
    // When select2 is an <input> it requires comma-separated values instead of an array
    if (in_array($type, ['text', 'hidden']) && str_contains($class, 'crm-select2') && is_array($element->getValue())) {
      $element->setValue(implode(',', $element->getValue()));
    }

    if ($type == 'select' && $element->getAttribute('multiple')) {
      $type = 'multiselect';
    }
    // Add widget-specific class
    if (!$class || !str_contains($class, 'crm-form-')) {
      $class = ($class ? "$class " : '') . 'crm-form-' . $type;
    }
    elseif (str_contains($class, 'crm-form-entityref')) {
      self::preProcessEntityRef($element);
    }
    elseif (str_contains($class, 'crm-form-autocomplete')) {
      self::preProcessAutocomplete($element);
    }
    elseif (str_contains($class, 'crm-form-contact-reference')) {
      self::preprocessContactReference($element);
    }
    // Hack to support html5 fields (number, url, etc)
    else {
      foreach (CRM_Core_Form::$html5Types as $type) {
        if (str_contains($class, "crm-form-$type")) {
          $element->setAttribute('type', $type);
          // Also add the "base" class for consistent styling
          $class .= ' crm-form-text';
          break;
        }
      }
    }

    if ($required) {
      $class .= ' required';
    }

    if ($error) {
      $class .= ' error';
    }

    $attributes['class'] = $class;
    $element->updateAttributes($attributes);
  }

  /**
   * Process an template sourced in a string with Smarty
   *
   * This overrides the quick form function which has not been updated in a while.
   *
   * The function is called when render the code to mark a field as 'required'
   *
   * The notes on the quick form function seem to refer to older smarty - ie:
   * Smarty has no core function to render a template given as a string.
   * So we use the smarty eval plugin function to do this.
   *
   * @param string $tplSource The template source
   */
  public function _tplFetch($tplSource) {
    // Smarty3 does not have this function defined so the parent fails.
    // Adding this is preparatory to smarty 3....
    if (!function_exists('smarty_function_eval') && (!defined('SMARTY_DIR') || !file_exists(SMARTY_DIR . '/plugins/function.eval.php'))) {
      $smarty = $this->_tpl;
      $smarty->assign('var', $tplSource);
      return $smarty->fetch("eval:$tplSource");
    }
    // This part is what the parent does & is suitable to Smarty 2.
    if (!function_exists('smarty_function_eval')) {
      require SMARTY_DIR . '/plugins/function.eval.php';
    }
    return smarty_function_eval(['var' => $tplSource], $this->_tpl);
  }

  /**
   * @param HTML_QuickForm_element $field
   */
  private static function preProcessAutocomplete($field) {
    $val = $field->getValue();
    if (is_array($val)) {
      $field->setValue(implode(',', $val));
    }
  }

  /**
   * Convert IDs to values and format for display.
   *
   * @param HTML_QuickForm_element $field
   */
  public static function preProcessEntityRef($field) {
    $val = $field->getValue();
    // Temporarily convert string values to an array
    if (!is_array($val)) {
      // Try to auto-detect method of serialization
      $val = strpos(($val ?? ''), ',') ? explode(',', str_replace(', ', ',', ($val ?? ''))) : (array) CRM_Utils_Array::explodePadded($val);
    }
    if ($val) {
      $entity = $field->getAttribute('data-api-entity');
      // Get api params, ensure it is an array
      $params = $field->getAttribute('data-api-params');
      $params = $params ? json_decode($params, TRUE) : [];
      $result = civicrm_api3($entity, 'getlist', ['id' => $val] + $params);
      // Purify label output of entityreference fields
      if (!empty($result['values'])) {
        foreach ($result['values'] as &$res) {
          if (!empty($res['label'])) {
            $res['label'] = CRM_Utils_String::purifyHTML($res['label']);
          }
        }
      }
      if ($field->isFrozen()) {
        // Prevent js from treating frozen entityRef as a "live" field
        $field->removeAttribute('class');
      }
      if (!empty($result['values'])) {
        $field->setAttribute('data-entity-value', json_encode($result['values']));
      }
      // CRM-15803 - Remove invalid values
      $val = array_intersect($val, CRM_Utils_Array::collect('id', $result['values']));
    }
    // Convert array values back to a string
    $field->setValue(implode(',', $val));
  }

  /**
   * Render datepicker as text.
   *
   * @param array $el
   * @param HTML_QuickForm_element $field
   */
  public function renderFrozenDatepicker(&$el, $field) {
    $settings = json_decode($field->getAttribute('data-crm-datepicker'), TRUE);
    $settings += ['date' => TRUE, 'time' => TRUE];
    $val = $field->getValue();
    if ($val) {
      $dateFormat = NULL;
      if (!$settings['time']) {
        $val = substr($val, 0, 10);
      }
      elseif (!$settings['date']) {
        $dateFormat = Civi::settings()->get('dateformatTime');
      }
      $val = CRM_Utils_Date::customFormat($val, $dateFormat);
    }
    $el['html'] = $val . '<input type="hidden" value="' . $field->getValue() . '" name="' . $field->getAttribute('name') . '">';
  }

  /**
   * Render select2 as text.
   *
   * @param array $el
   * @param HTML_QuickForm_element $field
   */
  public function renderFrozenSelect2(&$el, $field) {
    $params = json_decode($field->getAttribute('data-select-params'), TRUE);
    $val = $field->getValue();
    if ($val && !empty($params['data'])) {
      $display = [];
      foreach (explode(',', $val) as $item) {
        $match = CRM_Utils_Array::findInTree($item, $params['data']);
        if (isset($match['text']) && strlen($match['text'])) {
          $display[] = CRM_Utils_String::purifyHTML($match['text']);
        }
      }
      $el['html'] = implode('; ', $display) . '<input type="hidden" value="' . $field->getValue() . '" name="' . $field->getAttribute('name') . '">';
    }
  }

  /**
   * Render entity references as text.
   * If user has permission, format as link (for now limited to contacts).
   *
   * @param array $el
   * @param HTML_QuickForm_element $field
   */
  public function renderFrozenEntityRef(&$el, $field) {
    $entity = $field->getAttribute('data-api-entity');
    $vals = json_decode($field->getAttribute('data-entity-value'), TRUE);
    $display = [];

    // Custom fields of type contactRef store their data in a slightly different format
    if ($field->getAttribute('data-crm-custom') && $entity == 'Contact') {
      $vals = [['id' => $vals['id'], 'label' => $vals['text']]];
    }

    foreach ($vals as $val) {
      // Format contact as link
      if ($entity == 'Contact' && CRM_Contact_BAO_Contact_Permission::allow($val['id'], CRM_Core_Permission::VIEW)) {
        $url = CRM_Utils_System::url("civicrm/contact/view", ['reset' => 1, 'cid' => $val['id']]);
        $val['label'] = '<a class="view-contact no-popup" href="' . $url . '" title="' . ts('View Contact', ['escape' => 'htmlattribute']) . '">' . CRM_Utils_String::purifyHTML($val['label']) . '</a>';
      }
      $display[] = $val['label'];
    }

    $el['html'] = implode('; ', $display) . '<input type="hidden" value="' . $field->getValue() . '" name="' . $field->getAttribute('name') . '">';
  }

  /**
   * Pre-fill contact name for a custom field of type ContactReference
   *
   * Todo: Migrate contact reference fields to use EntityRef
   *
   * @param HTML_QuickForm_element $field
   */
  public static function preprocessContactReference($field) {
    $val = $field->getValue();
    $multiple = $field->getAttribute('multiple');
    $data = [];
    if ($val) {

      $list = array_keys(CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_reference_options'
      ), '1');

      $return = array_unique(array_merge(['sort_name'], $list));

      $cids = is_array($val) ? $val : explode(',', $val);

      foreach ($cids as $cid) {
        $contact = civicrm_api('contact', 'getsingle', ['id' => $cid, 'return' => $return, 'version' => 3]);
        if (!empty($contact['id'])) {
          $view = [];
          foreach ($return as $fld) {
            if (!empty($contact[$fld])) {
              $view[] = $contact[$fld];
            }
          }
          $data[] = [
            'id' => $contact['id'],
            'text' => implode(' :: ', $view),
          ];
        }
      }
    }

    if ($data) {
      $field->setAttribute('data-entity-value', json_encode($multiple ? $data : $data[0]));
      $field->setValue(implode(',', $cids));
    }
  }

  /**
   * @param array $el
   * @param HTML_QuickForm_element $field
   */
  public function addOptionsEditLink(&$el, $field) {
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      // NOTE: $path is used on the client-side to know which option lists need rebuilding,
      // that's why we need that bit of data both in the link and in the form element
      $path = $field->getAttribute('data-option-edit-path');
      // NOTE: If we ever needed to support arguments in this link other than reset=1 we could split $path here if it contains a ?
      $url = CRM_Utils_System::url($path, 'reset=1');
      $icon = CRM_Core_Page::crmIcon('fa-wrench', ts('Edit %1 Options', [1 => $field->getLabel() ?: ts('Field')]));
      $el['html'] .= <<<HEREDOC
 <a href="$url" class="crm-option-edit-link medium-popup crm-hover-button" target="_blank" data-option-edit-path="$path">$icon</a>
HEREDOC;
    }
  }

  /**
   * @param array $el
   * @param HTML_QuickForm_element $field
   */
  public function appendUnselectButton(&$el, $field) {
    // Initially hide if not needed
    // Note: visibility:hidden prevents layout jumping around unlike display:none
    $display = $field->getValue() !== NULL ? '' : ' style="visibility:hidden;"';
    $el['html'] .= ' <a href="#" class="crm-hover-button crm-clear-link"' . $display . ' title="' . ts('Clear', ['escape' => 'htmlattribute']) . '"><i class="crm-i fa-times" role="img" aria-hidden="true"></i></a>';
  }

}
