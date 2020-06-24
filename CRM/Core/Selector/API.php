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
 * This interface defines the set of functions a class needs to implement
 * to use the CRM/Selector object.
 *
 * Using this interface allows us to standardize on multiple things including
 * list display, pagination, sorting and export in multiple formats (CSV is
 * supported right now, XML support will be added as and when needed
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
interface CRM_Core_Selector_API {

  /**
   * Get pager parameters.
   *
   * Based on the action, the GET variables and the session state
   * it adds various key => value pairs to the params array including
   *
   *  status    - the status message to display. Modifiers will be defined
   *              to integrate the total count and the current state of the
   *              page: e.g. Displaying Page 3 of 5
   *  csvString - The html string to display for export as csv
   *  rowCount  - the number of rows to be included
   *
   * @param string $action
   *   The action being performed.
   * @param array $params
   *   The array that the pagerParams will be inserted into.
   */
  public function getPagerParams($action, &$params);

  /**
   * Returns the sort order array for the given action.
   *
   * @param string $action
   *   The action being performed.
   *
   * @return array
   *   the elements that can be sorted along with their properties
   */
  public function &getSortOrder($action);

  /**
   * Returns the column headers as an array of tuples.
   *
   * (name, sortName (key to the sort array))
   *
   * @param string $action
   *   The action being performed.
   * @param string $type
   *   What should the result set include (web/email/csv).
   *
   * @return array
   *   the column headers that need to be displayed
   */
  public function &getColumnHeaders($action = NULL, $type = NULL);

  /**
   * Returns the number of rows for this action.
   *
   * @param string $action
   *   The action being performed.
   *
   * @return int
   *   the total number of rows for this action
   */
  public function getTotalCount($action);

  /**
   * Returns all the rows in the given offset and rowCount.
   *
   * @param string $action
   *   The action being performed.
   * @param int $offset
   *   The row number to start from.
   * @param int $rowCount
   *   The number of rows to return.
   * @param string $sort
   *   The sql string that describes the sort order.
   * @param string $type
   *   What should the result set include (web/email/csv).
   *
   * @return int
   *   the total number of rows for this action
   */
  public function &getRows($action, $offset, $rowCount, $sort, $type = NULL);

  /**
   * Return the template (.tpl) filename.
   *
   * @param string $action
   *   The action being performed.
   *
   * @return string
   */
  public function getTemplateFileName($action = NULL);

  /**
   * Return the filename for the exported CSV.
   *
   * @param string $type
   *   The type of export required: csv/xml/foaf etc.
   *
   * @return string
   *   the fileName which we will munge to skip spaces and
   *                special characters to avoid various browser issues
   */
  public function getExportFileName($type = 'csv');

}
