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
class CRM_Core_ShowHideBlocks {

  /**
   * The array of ids of blocks that will be shown.
   *
   * @var array
   */
  protected $_show;

  /**
   * The array of ids of blocks that will be hidden.
   *
   * @var array
   */
  protected $_hide;

  /**
   * Class constructor.
   *
   * @param array|null $show
   *   Initial value of show array.
   * @param array|null $hide
   *   Initial value of hide array.
   *
   * @return \CRM_Core_ShowHideBlocks the newly created object
   */
  public function __construct($show = NULL, $hide = NULL) {
    if (!empty($show)) {
      $this->_show = $show;
    }
    else {
      $this->_show = [];
    }

    if (!empty($hide)) {
      $this->_hide = $hide;
    }
    else {
      $this->_hide = [];
    }
  }

  /**
   * Add the values from this class to the template.
   */
  public function addToTemplate() {
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
    $template->ensureVariablesAreAssigned(['elemType']);
    $template->assign('hideBlocks', $hide);
    $template->assign('showBlocks', $show);
  }

  /**
   * Add a value to the show array.
   *
   * @param string $name
   *   Id to be added.
   */
  public function addShow($name) {
    $this->_show[$name] = 1;
    if (array_key_exists($name, $this->_hide)) {
      unset($this->_hide[$name]);
    }
  }

  /**
   * Add a value to the hide array.
   *
   * @param string $name
   *   Id to be added.
   */
  public function addHide($name) {
    $this->_hide[$name] = 1;
    if (array_key_exists($name, $this->_show)) {
      unset($this->_show[$name]);
    }
  }

}
