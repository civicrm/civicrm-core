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
 * A simple base class for objects that need to implement the selector api
 * interface. This class provides common functionality with regard to actions
 * and display names
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Core_Selector_Base {

  /**
   * the sort order which is computed from the columnHeaders
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
   * This function gets the attribute for the action that
   * it matches.
   *
   * @param string  match      the action to match against
   * @param string  attribute  the attribute to return ( name, link, title )
   *
   * @return string            the attribute that matches the action if any
   *
   * @access public
   *
   */
  function getActionAttribute($match, $attribute = 'name') {
    $links = &$this->links();

    foreach ($link as $action => $item) {
      if ($match & $action) {
        return $item[$attribute];
      }
    }
    return NULL;
  }

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
  static function &links() {
    return NULL;
  }

  /**
   * compose the template file name from the class name
   *
   * @param string $action the action being performed
   *
   * @return string template file name
   * @access public
   */
  function getTemplateFileName($action = NULL) {
    return (str_replace('_', DIRECTORY_SEPARATOR, CRM_Utils_System::getClassName($this)) . ".tpl");
  }

  /**
   * getter for the sorting direction for the fields which will be displayed on the form.
   *
   * @param string action the action being performed
   *
   * @return array the elements that can be sorted along with their properties
   * @access public
   */
  function &getSortOrder($action) {
    $columnHeaders = &$this->getColumnHeaders(NULL);

    if (!isset($this->_order)) {
      $this->_order = array();
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
      if ($firstElementNotFound) {
        // CRM_Core_Error::fatal( "Could not find a valid sort directional element" );
      }
    }
    return $this->_order;
  }

  /**
   * setter for permission
   *
   * @var string
   * @access public
   */
  public function setPermission($permission) {
    $this->_permission = $permission;
  }

  /**
   * get the display text in plain language for the search
   * to display on the results page
   *
   * @return string
   * @access public
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

