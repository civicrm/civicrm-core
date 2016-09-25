<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */
interface CRM_Contact_Form_Search_Interface {

  /**
   * The constructor gets the submitted form values.
   *
   * @param array $formValues
   */
  public function __construct(&$formValues);

  /**
   * Builds the list of tasks or actions that a searcher can perform on a result set.
   *
   * @param CRM_Core_Form_Search $form
   * @return array
   */
  public function buildTaskList(CRM_Core_Form_Search $form);

  /**
   * Builds the quickform for this search.
   *
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form);

  /**
   * Builds the search query for various cases. We break it down into finer cases
   * since you can optimize each query independently. All the functions below return
   * a sql clause with only SELECT, FROM, WHERE sub-parts. The ORDER BY and LIMIT is
   * added at a later stage
   */

  /**
   * Count of records that match the current input parameters.
   *
   * Used by pager.
   */
  public function count();

  /**
   * Summary information for the query that can be displayed in the template.
   *
   * This is useful to pass total / sub total information if needed
   */
  public function summary();

  /**
   * List of contact ids that match the current input parameters.
   *
   * Used by different tasks. Will be also used to optimize the
   * 'all' query below to avoid excessive LEFT JOIN blowup
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   */
  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL);

  /**
   * Retrieve all the values that match the current input parameters.
   *
   * Used by the selector
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   */
  public function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE);

  /**
   * The below two functions (from and where) are ONLY used if you want to
   * expose a custom group as a smart group and be able to send a mailing
   * to them via CiviMail. civicrm_email should be part of the from clause
   * The from clause should be a valid sql from clause including the word FROM
   * CiviMail will pick up the contacts where the email is primary and
   * is not on hold / opt out / do not email
   */

  /**
   * The from clause for the query.
   */
  public function from();

  /**
   * The where clause for the query.
   *
   * @param bool $includeContactIDs
   */
  public function where($includeContactIDs = FALSE);

  /**
   * The template FileName to use to display the results.
   */
  public function templateFile();

  /**
   * Returns an array of column headers and field names and sort options.
   */
  public function &columns();

}
