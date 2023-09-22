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
 * Base class to provide generic sort functionality.
 *
 * Note that some ideas have been borrowed from the drupal tablesort.inc code.
 *
 * Also note that since the Pager and Sort class are similar, do match the function names
 * if introducing additional functionality
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Sort {

  /**
   * Constants to determine what direction each variable
   * is to be sorted
   *
   * @var int
   */
  const ASCENDING = 1, DESCENDING = 2, DONTCARE = 4;

  /**
   * The name for the sort GET/POST param
   *
   * @var string
   */
  const SORT_ID = 'crmSID', SORT_DIRECTION = 'crmSortDirection', SORT_ORDER = 'crmSortOrder';

  /**
   * Name of the sort function. Used to isolate session variables
   * @var string
   */
  protected $_name;

  /**
   * Array of variables that influence the query
   *
   * @var array
   */
  public $_vars;

  /**
   * The newly formulated base url to be used as links
   * for various table elements
   *
   * @var string
   */
  protected $_link;

  /**
   * What's the name of the sort variable in a REQUEST
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
   * @param mixed $vars
   *   Assoc array as described above.
   * @param string $defaultSortOrder
   *   Order to sort.
   *
   * @return \CRM_Utils_Sort
   */
  public function __construct($vars, $defaultSortOrder = NULL) {
    $this->_vars = [];
    $this->_response = [];

    foreach ($vars as $weight => $value) {
      $this->_vars[$weight] = [
        'name' => CRM_Utils_Type::validate($value['sort'], 'MysqlColumnNameOrAlias'),
        'direction' => $value['direction'] ?? NULL,
        'title' => $value['name'],
      ];
    }

    $this->_currentSortID = 1;
    if (isset($this->_vars[$this->_currentSortID])) {
      $this->_currentSortDirection = $this->_vars[$this->_currentSortID]['direction'];
    }
    $this->_urlVar = self::SORT_ID;
    $this->_link = CRM_Utils_System::makeURL($this->_urlVar, TRUE);

    $this->initialize($defaultSortOrder);
  }

  /**
   * Function returns the string for the order by clause.
   *
   * @return string
   *   the order by clause
   */
  public function orderBy() {
    if (empty($this->_vars[$this->_currentSortID])) {
      return '';
    }

    if ($this->_vars[$this->_currentSortID]['direction'] == self::ASCENDING ||
      $this->_vars[$this->_currentSortID]['direction'] == self::DONTCARE
    ) {
      $this->_vars[$this->_currentSortID]['name'] = str_replace(' ', '_', $this->_vars[$this->_currentSortID]['name']);
      return CRM_Utils_Type::escape($this->_vars[$this->_currentSortID]['name'], 'MysqlColumnNameOrAlias') . ' asc';
    }
    else {
      $this->_vars[$this->_currentSortID]['name'] = str_replace(' ', '_', $this->_vars[$this->_currentSortID]['name']);
      return CRM_Utils_Type::escape($this->_vars[$this->_currentSortID]['name'], 'MysqlColumnNameOrAlias') . ' desc';
    }
  }

  /**
   * Create the sortID string to be used in the GET param.
   *
   * @param int $index
   *   The field index.
   * @param int $dir
   *   The direction of the sort.
   *
   * @return string
   *   the string to append to the url
   */
  public static function sortIDValue($index, $dir) {
    return ($dir == self::DESCENDING) ? $index . '_d' : $index . '_u';
  }

  /**
   * Init the sort ID values in the object.
   *
   * @param string $defaultSortOrder
   *   The sort order to use by default.
   */
  public function initSortID($defaultSortOrder) {
    $url = CRM_Utils_Array::value(self::SORT_ID, $_GET, $defaultSortOrder);

    if (empty($url)) {
      return;
    }

    list($current, $direction) = explode('_', $url);

    // if current is weird and does not exist in the vars array, skip
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
   * Init the object.
   *
   * @param string $defaultSortOrder
   *   The sort order to use by default.
   */
  public function initialize($defaultSortOrder) {
    $this->initSortID($defaultSortOrder);

    $this->_response = [];

    $current = $this->_currentSortID;
    foreach ($this->_vars as $index => $item) {
      $name = $item['name'];
      $this->_response[$name] = [];

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
   * Getter for currentSortID.
   *
   * @return int
   *   returns of the current sort id
   */
  public function getCurrentSortID() {
    return $this->_currentSortID;
  }

  /**
   * Getter for currentSortDirection.
   *
   * @return int
   *   returns of the current sort direction
   */
  public function getCurrentSortDirection() {
    return $this->_currentSortDirection;
  }

  /**
   * Universal callback function for sorting by weight, id, title or name
   *
   * @param array $a
   * @param array $b
   *
   * @return int
   *   (-1 or 1)
   */
  public static function cmpFunc($a, $b) {
    $cmp_order = ['weight', 'id', 'title', 'name'];
    foreach ($cmp_order as $attribute) {
      if (isset($a[$attribute]) && isset($b[$attribute])) {
        if ($a[$attribute] < $b[$attribute]) {
          return -1;
        }
        elseif ($a[$attribute] > $b[$attribute]) {
          return 1;
        } // else: $a and $b are equal wrt to this attribute, try next...
      }
    }
    // if we get here, $a and $b are equal for all we know
    // however, as I understand we don't want equality here:
    return -1;
  }

}
