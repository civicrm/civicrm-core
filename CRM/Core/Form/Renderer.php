<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
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
   * @param  object    An HTML_QuickForm_element object
   * @param  bool      Whether an element is required
   * @param  string    Error associated with the element
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

    return $el;
  }

  /**
   * Update the attributes of this element and add a few CiviCRM
   * based attributes so we can style this form element better
   *
   * @access private
   *
   * @param  object    An HTML_QuickForm_element object
   * @param  bool      Whether an element is required
   * @param  string    Error associated with the element
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
    if (empty($class)) {
      $class = 'form-' . $type;

      if ($type == 'text') {
        $size = $element->getAttribute('size');
        if (!empty($size)) {
          if (array_key_exists($size, self::$_sizeMapper)) {
            $class = $class . ' ' . self::$_sizeMapper[$size];
          }
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
}
// end CRM_Core_Form_Renderer

