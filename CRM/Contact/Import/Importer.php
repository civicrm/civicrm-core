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
 | Version 3, 19 November 2009.                                       |
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

/**
 * This class mainly exists to allow imports to be triggered synchronously (i.e.
 *  via a form post) and asynchronously (i.e. by the workflow system)
 */
class CRM_Contact_Import_Importer {
  /**
   *
   */
  public function __construct() {
    // may not need this
  }

  /**
   * @param int $timeout
   */
  public function runIncompleteImportJobs($timeout = 55) {
    $startTime = time();
    $incompleteImportTables = CRM_Contact_Import_ImportJob::getIncompleteImportTables();
    foreach ($incompleteImportTables as $importTable) {
      $importJob = new CRM_Contact_Import_ImportJob($importTable);
      $importJob->runImport(NULL, $timeout);
      $currentTime = time();
      if (($currentTime - $startTime) >= $timeout) {
        break;
      }
    }
  }
}

