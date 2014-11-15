<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

require_once 'HTML/QuickForm/Renderer/ArraySmarty.php';

/**
 * customize the output to meet our specific requirements
 */
class CRM_Core_Form_Renderer extends HTML_QuickForm_Renderer_ArraySmarty {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * the converter from array size to css class
   *
   * @var array
   * @static
   */
  static $_sizeMapper = array(
    2 => 'two',
    4 => 'four',
    6 => 'six',
    8 => 'eight',
    12 => 'twelve',
    20 => 'medium',
    30 => 'big',
    45 => 'huge',
  );

  /**
   * Constructor
   *
   * @access public
   */ function __construct() {
    $template = CRM_Core_Smarty::singleton();
    parent::__construct($template);
  }

  /**
   * Static instance provider.
   *
   * Method providing static instance of as in Singleton pattern.
   */
  static function &singleton() {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_Core_Form_Renderer();
    }
    return self::$_singleton;
  }

  /**
   * Creates an array representing an element containing
   * the key for storing this. We allow the parent to do most of the
   * work, but then we add some CiviCRM specific enhancements to
   * make the html compliant with our css etc
   *
   * @access private
   *
   * @param  $element HTML_QuickForm_element
   * @param  $required bool - Whether an element is required
   * @param  $error string - Error associated with the element
   *
   * @return array
   */
  function _elementToArray(&$element, $required, $error) {
    self::updateAttributes($element, $required, $error);

    $el = parent::_elementToArray($element, $required, $error);

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
      $el['html'] = '<span class="crm-frozen-field">' . $el['html'] . '</span>';
    }
    // Active form elements
    else {
      if ($element->getType() == 'select' && $element->getAttribute('data-option-edit-path')) {
        $this->addOptionsEditLink($el, $element);
      }

      if ($element->getType() == 'group' && $element->getAttribute('allowClear')) {
        $this->appendUnselectButton($el, $element);
      }
    }

    return $el;
  }

  /**
   * Update the attributes of this element and add a few CiviCRM
   * based attributes so we can style this form element better
   *
   * @access private
   *
   * @param  $element  HTML_QuickForm_element object
   * @param  $required bool      Whether an element is required
   * @param  $error    string    Error associated with the element
   *
   * @return array
   * @static
   */
  static function updateAttributes(&$element, $required, $error) {
    // lets create an id for all input elements, so we can generate nice label tags
    // to make it nice and clean, we'll just use the elementName if it is non null
    $attributes = array();
    if (!$element->getAttribute('id')) {
      $name = $element->getAttribute('name');
      if ($name) {
        $attributes['id'] = str_replace(array(']', '['),
          array('', '_'),
          $name
        );
      }
    }

    $class = $element->getAttribute('class');
    $type = $element->getType();
    if (!$class) {
      if ($type == 'text') {
        $size = $element->getAttribute('size');
        if (!empty($size)) {
          $class = CRM_Utils_Array::value($size, self::$_sizeMapper);
        }
      }
    }

    if ($type == 'select' && $element->getAttribute('multiple')) {
      $type = 'multiselect';
    }
    // Add widget-specific class
    if (!$class || strpos($class, 'crm-form-') === FALSE) {
      $class = ($class ? "$class " : '') . 'crm-form-' . $type;
    }
    elseif (strpos($class, 'crm-form-entityref') !== FALSE) {
      self::preProcessEntityRef($element);
    }
    elseif (strpos($class, 'crm-form-contact-reference') !== FALSE) {
      self::preprocessContactReference($element);
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
   * Convert IDs to values and format for display
   *
   * @param $field HTML_QuickForm_element
   */
  static function preProcessEntityRef($field) {
    $val = $field->getValue();
    // Support array values
    if (is_array($val)) {
      $val = implode(',', $val);
      $field->setValue($val);
    }
    if ($val) {
      $entity = $field->getAttribute('data-api-entity');
      // Get api params, ensure it is an array
      $params = $field->getAttribute('data-api-params');
      $params = $params ? json_decode($params, TRUE) : array();
      // Support serialized values
      if (strpos($val, CRM_Core_DAO::VALUE_SEPARATOR) !== FALSE) {
        $val = str_replace(CRM_Core_DAO::VALUE_SEPARATOR, ',', trim($val, CRM_Core_DAO::VALUE_SEPARATOR));
        $field->setValue($val);
      }
      $result = civicrm_api3($entity, 'getlist', array('id' => $val) + $params);
      if ($field->isFrozen()) {
        $field->removeAttribute('class');
      }
      if (!empty($result['values'])) {
        $field->setAttribute('data-entity-value', json_encode($result['values']));
      }
    }
  }

  /**
   * Render entity references as text.
   * If user has permission, format as link (for now limited to contacts).
   * @param $el array
   * @param $field HTML_QuickForm_element
   */
  function renderFrozenEntityRef(&$el, $field) {
    $entity = $field->getAttribute('data-api-entity');
    $vals = json_decode($field->getAttribute('data-entity-value'), TRUE);
    $display = array();

    // Custom fields of type contactRef store their data in a slightly different format
    if ($field->getAttribute('data-crm-custom') && $entity == 'contact') {
      $vals = array(array('id' => $vals['id'], 'label' => $vals['text']));
    }

    foreach ($vals as $val) {
      // Format contact as link
      if ($entity == 'contact' && CRM_Contact_BAO_Contact_Permission::allow($val['id'], CRM_Core_Permission::VIEW)) {
        $url = CRM_Utils_System::url("civicrm/contact/view", array('reset' => 1, 'cid' => $val['id']));
        $val['label'] = '<a class="view-' . $entity . ' no-popup" href="' . $url . '" title="' . ts('View Contact') . '">' . $val['label'] . '</a>';
      }
      $display[] = $val['label'];
    }

    $el['html'] = implode('; ', $display) . '<input type="hidden" value="'. $field->getValue() . '" name="' . $field->getAttribute('name') . '">';
  }

  /**
   * Pre-fill contact name for a custom field of type ContactReference
   *
   * Todo: Migrate contact reference fields to use EntityRef
   *
   * @param $field HTML_QuickForm_element
   */
  static function preprocessContactReference($field) {
    $val = $field->getValue();
    if ($val && is_numeric($val)) {

      $list = array_keys(CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_reference_options'
      ), '1');

      $return = array_unique(array_merge(array('sort_name'), $list));

      $contact = civicrm_api('contact', 'getsingle', array('id' => $val, 'return' => $return, 'version' => 3));

      if (!empty($contact['id'])) {
        $view = array();
        foreach ($return as $fld) {
          if (!empty($contact[$fld])) {
            $view[] = $contact[$fld];
          }
        }
        $field->setAttribute('data-entity-value', json_encode(array('id' => $contact['id'], 'text' => implode(' :: ', $view))));
      }
    }
  }

  /**
   * @param array $el
   * @param HTML_QuickForm_element $field
   */
  function addOptionsEditLink(&$el, $field) {
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      // NOTE: $path is used on the client-side to know which option lists need rebuilding,
      // that's why we need that bit of data both in the link and in the form element
      $path = $field->getAttribute('data-option-edit-path');
      // NOTE: If we ever needed to support arguments in this link other than reset=1 we could split $path here if it contains a ?
      $url = CRM_Utils_System::url($path, 'reset=1');
      $el['html'] .= ' <a href="' . $url . '" class="crm-option-edit-link medium-popup crm-hover-button" target="_blank" title="' . ts('Edit Options') . '" data-option-edit-path="' . $path . '"><span class="icon ui-icon-wrench"></span></a>';
    }
  }

  /**
   * @param array $el
   * @param HTML_QuickForm_element $field
   */
  function appendUnselectButton(&$el, $field) {
    // Initially hide if not needed
    // Note: visibility:hidden prevents layout jumping around unlike display:none
    $display = $field->getValue() !== NULL ? '' : ' style="visibility:hidden;"';
    $el['html'] .= ' <a href="#" class="crm-hover-button crm-clear-link"' . $display . ' title="' . ts('Clear') . '"><span class="icon close-icon"></span></a>';
  }
}

