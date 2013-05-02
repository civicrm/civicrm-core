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
 * This interface defines the set of functions a class needs to implement
 * to use the CRM/Selector object.
 *
 * Using this interface allows us to standardize on multiple things including
 * list display, pagination, sorting and export in multiple formats (CSV is
 * supported right now, XML support will be added as and when needed
 *
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
interface CRM_Core_Selector_API {

  /**
   * Based on the action, the GET variables and the session state
   * it adds various key => value pairs to the params array including
   *
   *  status    - the status message to display. Modifiers will be defined
   *              to integrate the total count and the current state of the
   *              page: e.g. Displaying Page 3 of 5
   *  csvString - The html string to display for export as csv
   *  rowCount  - the number of rows to be included
   *
   * @param string action the action being performed
   * @param array  params the array that the pagerParams will be inserted into
   *
   * @return void
   *
   * @access public
   *
   */
  function getPagerParams($action, &$params);

  /**
   * returns the sort order array for the given action
   *
   * @param string action the action being performed
   *
   * @return array the elements that can be sorted along with their properties
   * @access public
   *
   */
  function &getSortOrder($action);

  /**
   * returns the column headers as an array of tuples:
   * (name, sortName (key to the sort array))
   *
   * @param string $action the action being performed
   * @param enum   $type   what should the result set include (web/email/csv)
   *
   * @return array the column headers that need to be displayed
   * @access public
   */
  function &getColumnHeaders($action = NULL, $type = NULL);

  /**
   * returns the number of rows for this action
   *
   * @param string action the action being performed
   *
   * @return int   the total number of rows for this action
   *
   * @access public
   *
   */
  function getTotalCount($action);

  /**
   * returns all the rows in the given offset and rowCount
   *
   * @param enum   $action   the action being performed
   * @param int    $offset   the row number to start from
   * @param int    $rowCount the number of rows to return
   * @param string $sort     the sql string that describes the sort order
   * @param enum   $type     what should the result set include (web/email/csv)
   *
   * @return int   the total number of rows for this action
   * @access public
   */
  function &getRows($action, $offset, $rowCount, $sort, $type = NULL);

  /**
   * return the template (.tpl) filename
   *
   * @param string $action the action being performed
   *
   * @return string
   * @access public
   *
   */
  function getTemplateFileName($action = NULL);

  /**
   * return the filename for the exported CSV
   *
   * @param string type   the type of export required: csv/xml/foaf etc
   *
   * @return string the fileName which we will munge to skip spaces and
   *                special characters to avoid various browser issues
   *
   */
  function getExportFileName($type = 'csv');
}

