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

/**
 *
 * Base class to provide generic sort functionality. Note that some ideas
 * have been borrowed from the drupal tablesort.inc code. Also note that
 * since the Pager and Sort class are similar, do match the function names
 * if introducing additional functionality
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Utils_Sort {

  /**
   * constants to determine what direction each variable
   * is to be sorted
   *
   * @var int
   */
  CONST ASCENDING = 1, DESCENDING = 2, DONTCARE = 4,

  /**
   * the name for the sort GET/POST param
   *
   * @var string
   */
  SORT_ID = 'crmSID', SORT_DIRECTION = 'crmSortDirection', SORT_ORDER = 'crmSortOrder';

  /**
   * name of the sort function. Used to isolate session variables
   * @var string
   */
  protected $_name;

  /**
   * array of variables that influence the query
   *
   * @var array
   */
  public $_vars;

  /**
   * the newly formulated base url to be used as links
   * for various table elements
   *
   * @var string
   */
  protected $_link;

  /**
   * what's the name of the sort variable in a REQUEST
   *
   * @var string
   */
  protected $_urlVar;

  /**
   * What variable are we currently sorting on
   *
   * @var string
   */
  protected $_currentSortID;

  /**
   * What direction are we sorting on
   *
   * @var string
   */
  protected $_currentSortDirection;

  /**
   * The output generated for the current form
   *
   * @var array
   */
  public $_response;

  /**
   * The constructor takes an assoc array
   * key names of variable (which should be the same as the column name)
   * value: ascending or descending
   *
   * @param mixed  $vars             - assoc array as described above
   * @param string $defaultSortOrder - order to sort
   *
   * @return void
   * @access public
   */ 
  function __construct(&$vars, $defaultSortOrder = NULL) {
    $this->_vars = array();
    $this->_response = array();

    foreach ($vars as $weight => $value) {
      $this->_vars[$weight] = array(
        'name' => $value['sort'],
        'direction' => CRM_Utils_Array::value('direction', $value),
        'title' => $value['name'],
      );
    }

    $this->_currentSortID = 1;
    if (isset($this->_vars[$this->_currentSortID])) {
      $this->_currentSortDirection = $this->_vars[$this->_currentSortID]['direction'];
    }
    $this->_urlVar = self::SORT_ID;
    $this->_link = CRM_Utils_System::makeURL($this->_urlVar);

    $this->initialize($defaultSortOrder);
  }

  /**
   * Function returns the string for the order by clause
   *
   * @return string the order by clause
   * @access public
   */
  function orderBy() {
    if (!CRM_Utils_Array::value($this->_currentSortID, $this->_vars)) {
      return '';
    }

    if ($this->_vars[$this->_currentSortID]['direction'] == self::ASCENDING ||
      $this->_vars[$this->_currentSortID]['direction'] == self::DONTCARE
    ) {
      $this->_vars[$this->_currentSortID]['name'] = str_replace(' ', '_', $this->_vars[$this->_currentSortID]['name']);
      return $this->_vars[$this->_currentSortID]['name'] . ' asc';
    }
    else {
      $this->_vars[$this->_currentSortID]['name'] = str_replace(' ', '_', $this->_vars[$this->_currentSortID]['name']);
      return $this->_vars[$this->_currentSortID]['name'] . ' desc';
    }
  }

  /**
   * create the sortID string to be used in the GET param
   *
   * @param int $index the field index
   * @param int $dir   the direction of the sort
   *
   * @return string  the string to append to the url
   * @static
   * @access public
   */
  static function sortIDValue($index, $dir) {
    return ($dir == self::DESCENDING) ? $index . '_d' : $index . '_u';
  }

  /**
   * init the sort ID values in the object
   *
   * @param string $defaultSortOrder the sort order to use by default
   *
   * @return returns null if $url- (sort url) is not found
   * @access public
   */
  function initSortID($defaultSortOrder) {
    $url = CRM_Utils_Array::value(self::SORT_ID, $_GET, $defaultSortOrder);

    if (empty($url)) {
      return;
    }

    list($current, $direction) = explode('_', $url);

    // if current is wierd and does not exist in the vars array, skip
    if (!array_key_exists($current, $this->_vars)) {
      return;
    }

    if ($direction == 'u') {
      $direction = self::ASCENDING;
    }
    elseif ($direction == 'd') {
      $direction = self::DESCENDING;
    }
    else {
      $direction = self::DONTCARE;
    }

    $this->_currentSortID = $current;
    $this->_currentSortDirection = $direction;
    $this->_vars[$current]['direction'] = $direction;
  }

  /**
   * init the object
   *
   * @param string $defaultSortOrder the sort order to use by default
   *
   * @return void
   * @access public
   */
  function initialize($defaultSortOrder) {
    $this->initSortID($defaultSortOrder);

    $this->_response = array();

    $current = $this->_currentSortID;
    foreach ($this->_vars as $index => $item) {
      $name = $item['name'];
      $this->_response[$name] = array();

      $newDirection = ($item['direction'] == self::ASCENDING) ? self::DESCENDING : self::ASCENDING;

      if ($current == $index) {
        if ($item['direction'] == self::ASCENDING) {
          $class = 'sorting_asc';
        }
        else {
          $class = 'sorting_desc';
        }
      }
      else {
        $class = 'sorting';
      }

      $this->_response[$name]['link'] = '<a href="' . $this->_link . $this->sortIDValue($index, $newDirection) . '" class="' . $class . '">' . $item['title'] . '</a>';
    }
  }

  /**
   * getter for currentSortID
   *
   * @return int returns of the current sort id
   * @acccess public
   */
  function getCurrentSortID() {
    return $this->_currentSortID;
  }

  /**
   * getter for currentSortDirection
   *
   * @return int returns of the current sort direction
   * @acccess public
   */
  function getCurrentSortDirection() {
    return $this->_currentSortDirection;
  }

  /**
   * Universal callback function for sorting by weight
   *
   * @return array of items sorted by weight
   * @access public
   */
  static function cmpFunc($a, $b) {
    return ($a['weight'] <= $b['weight']) ? -1 : 1;
  }
}

