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
class CRM_Core_ShowHideBlocks {

  /**
   * The icons prefixed to block show and hide links.
   *
   * @var string
   */
  static $_showIcon, $_hideIcon;

  /**
   * The array of ids of blocks that will be shown
   *
   * @var array
   */
  protected $_show;

  /**
   * The array of ids of blocks that will be hidden
   *
   * @var array
   */
  protected $_hide;

  /**
   * class constructor
   *
   * @param array $show initial value of show array
   * @param array $hide initial value of hide array
   *
   * @return \CRM_Core_ShowHideBlocks the newly created object@access public
   */
  function __construct($show = NULL, $hide = NULL) {
    if (!empty($show)) {
      $this->_show = $show;
    }
    else {
      $this->_show = array();
    }

    if (!empty($hide)) {
      $this->_hide = $hide;
    }
    else {
      $this->_hide = array();
    }
  }

  /**
   * load icon vars used in hide and show links
   *
   * @return void
   * @access public
   * @static
   */
  static function setIcons() {
    if (!isset(self::$_showIcon)) {
      $config = CRM_Core_Config::singleton();
      self::$_showIcon = '<img src="' . $config->resourceBase . 'i/TreePlus.gif" class="action-icon" alt="' . ts('show field or section') . '"/>';
      self::$_hideIcon = '<img src="' . $config->resourceBase . 'i/TreeMinus.gif" class="action-icon" alt="' . ts('hide field or section') . '"/>';
    }
  }

  /**
   * add the values from this class to the template
   *
   * @return void
   * @access public
   */
  function addToTemplate() {
    $hide = $show = '';

    $first = TRUE;
    foreach (array_keys($this->_hide) as $h) {
      if (!$first) {
        $hide .= ',';
      }
      $hide .= "'$h'";
      $first = FALSE;
    }

    $first = TRUE;
    foreach (array_keys($this->_show) as $s) {
      if (!$first) {
        $show .= ',';
      }
      $show .= "'$s'";
      $first = FALSE;
    }

    $template = CRM_Core_Smarty::singleton();
    $template->assign_by_ref('hideBlocks', $hide);
    $template->assign_by_ref('showBlocks', $show);
  }

  /**
   * Add a value to the show array
   *
   * @param string $name id to be added
   *
   * @return void
   * @access public
   */
  function addShow($name) {
    $this->_show[$name] = 1;
    if (array_key_exists($name, $this->_hide)) {
      unset($this->_hide[$name]);
    }
  }

  /**
   * Add a value to the hide array
   *
   * @param string $name id to be added
   *
   * @return void
   * @access public
   */
  function addHide($name) {
    $this->_hide[$name] = 1;
    if (array_key_exists($name, $this->_show)) {
      unset($this->_show[$name]);
    }
  }

  /**
   * create a well formatted html link from the smaller pieces
   *
   * @param string $name name of the link
   * @param string $href
   * @param string $text
   * @param string $js
   *
   * @return string      the formatted html link
   * @access public
   */
  static function linkHtml($name, $href, $text, $js) {
    return '<a name="' . $name . '" id="' . $name . '" href="' . $href . '" ' . $js . ">$text</a>";
  }

  /**
   * Create links that we can use in the form
   *
   * @param CRM_Core_Form $form the form object
   * @param string $prefix the attribute that we are referencing
   * @param string $showLinkText the text to be shown for the show link
   * @param string $hideLinkText the text to be shown for the hide link
   *
   * @param bool $assign
   *
   * @static
   *
   * @return void
   * @access public
   */
  static function links(&$form, $prefix, $showLinkText, $hideLinkText, $assign = TRUE) {
    $showCode = "cj('#id_{$prefix}').show(); cj('#id_{$prefix}_show').hide();";
    $hideCode = "cj('#id_{$prefix}').hide(); cj('#id_{$prefix}_show').show(); return false;";

    self::setIcons();
    $values         = array();
    $values['show'] = self::linkHtml("${prefix}_show", "#${prefix}_hide", self::$_showIcon . $showLinkText, "onclick=\"$showCode\"");
    $values['hide'] = self::linkHtml("${prefix}_hide", "#${prefix}", self::$_hideIcon . $hideLinkText, "onclick=\"$hideCode\"");

    if ($assign) {
      $form->assign($prefix, $values);
    }
    else {
      return $values;
    }
  }

  /**
   * Create html link elements that we can use in the form
   *
   * @param CRM_Core_Form $form          the form object
   * @param int           $index         the current index of the element being processed
   * @param int           $maxIndex      the max number of elements that will be processed
   * @param string        $prefix        the attribute that we are referencing
   * @param string        $showLinkText  the text to be shown for the show link
   * @param string        $hideLinkText  the text to be shown for the hide link
   * @param string        $elementType   the set the class
   * @param string        $hideLink      the hide block string
   *
   * @return void
   * @access public
   */
  function linksForArray(&$form, $index, $maxIndex, $prefix, $showLinkText, $hideLinkText, $elementType = NULL, $hideLink = NULL) {
    $showHidePrefix = str_replace(array("]", "["), array("", "_"), $prefix);
    $showHidePrefix = "id_" . $showHidePrefix;
    if ($index == $maxIndex) {
      $showCode = $hideCode = "return false;";
    }
    else {
      $next = $index + 1;
      if ($elementType) {
        $showCode = "cj('#${prefix}_${next}_show').show(); return false;";
        if ($hideLink) {
          $hideCode = $hideLink;
        }
        else {
          $hideCode = "cj('#${prefix}_${next}_show, #${prefix}_${next}').hide(); return false;";
        }
      }
      else {
        $showCode = "cj('#{$showHidePrefix}_{$next}_show').show(); return false;";
        $hideCode = "cj('#{$showHidePrefix}_{$next}_show, #{$showHidePrefix}_{$next}').hide(); return false;";
      }
    }

    self::setIcons();
    if ($elementType) {
      $form->addElement('link', "${prefix}[${index}][show]", NULL, "#${prefix}_${index}", self::$_showIcon . $showLinkText,
        array('onclick' => "cj('#${prefix}_${index}_show').hide(); cj('#${prefix}_${index}').show();" . $showCode)
      );
      $form->addElement('link', "${prefix}[${index}][hide]", NULL, "#${prefix}_${index}", self::$_hideIcon . $hideLinkText,
        array('onclick' => "cj('#${prefix}_${index}').hide(); cj('#${prefix}_${index}_show').show();" . $hideCode)
      );
    }
    else {
      $form->addElement('link', "${prefix}[${index}][show]", NULL, "#${prefix}_${index}", self::$_showIcon . $showLinkText,
        array('onclick' => "cj('#{$showHidePrefix}_{$index}_show').hide(); cj('#{$showHidePrefix}_{$index}').show();" . $showCode)
      );
      $form->addElement('link', "${prefix}[${index}][hide]", NULL, "#${prefix}_${index}", self::$_hideIcon . $hideLinkText,
        array('onclick' => "cj('#{$showHidePrefix}_{$index}').hide(); cj('#{$showHidePrefix}_{$index}_show').show();" . $hideCode)
      );
    }
  }
}

