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
 * A simple base class for objects that need to implement the selector api
 * interface. This class provides common functionality with regard to actions
 * and display names
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_Selector_Base {

  /**
   * The sort order which is computed from the columnHeaders
   *
   * @var array
   */
  protected $_order;

  /**
   * The permission mask for this selector
   *
   * @var string
   */
  protected $_permission = NULL;

  /**
   * The qfKey of the underlying search
   *
   * @var string
   */
  protected $_key;

  /**
   * This is a static virtual function returning reference on links array. Each
   * inherited class must redefine this function
   *
   * links is an array of associative arrays. Each element of the array
   * has at least 3 fields
   *
   * name    : the name of the link
   * url     : the URI to be used for this link
   * qs      : the parameters to the above url along with any dynamic substitutions
   * title   : A more descriptive name, typically used in breadcrumbs / navigation
   */
  public static function &links() {
    return NULL;
  }

  /**
   * Compose the template file name from the class name.
   *
   * @param string $action
   *   The action being performed.
   *
   * @return string
   *   template file name
   */
  public function getTemplateFileName($action = NULL) {
    return (str_replace('_', DIRECTORY_SEPARATOR, CRM_Utils_System::getClassName($this)) . ".tpl");
  }

  /**
   * Getter for the sorting direction for the fields which will be displayed on the form.
   *
   * @param string $action the action being performed
   *
   * @return array
   *   the elements that can be sorted along with their properties
   */
  public function &getSortOrder($action) {
    $columnHeaders = &$this->getColumnHeaders(NULL);

    if (!isset($this->_order)) {
      $this->_order = [];
      $start = 2;
      $firstElementNotFound = TRUE;
      if (!empty($columnHeaders)) {
        foreach ($columnHeaders as $k => $header) {
          $header = &$columnHeaders[$k];
          if (array_key_exists('sort', $header)) {
            if ($firstElementNotFound && $header['direction'] != CRM_Utils_Sort::DONTCARE) {
              $this->_order[1] = &$header;
              $firstElementNotFound = FALSE;
            }
            else {
              $this->_order[$start++] = &$header;
            }
          }
          unset($header);
        }
      }
    }
    return $this->_order;
  }

  /**
   * Setter for permission.
   *
   * @var string
   */
  public function setPermission($permission) {
    $this->_permission = $permission;
  }

  /**
   * Get the display text in plain language for the search
   * to display on the results page
   *
   * FIXME: the current internationalisation is bad, but should more or less work
   * on most of "European" languages
   *
   * @return array
   *   array of strings
   */
  public function getQill() {
    return NULL;
  }

  /**
   * @return null
   */
  public function getSummary() {
    return NULL;
  }

  /**
   * @param $key
   */
  public function setKey($key) {
    $this->_key = $key;
  }

  /**
   * @return string
   */
  public function getKey() {
    return $this->_key;
  }

}
