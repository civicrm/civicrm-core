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
class CRM_SMS_Controller_Send extends CRM_Core_Controller {

  /**
   * class constructor
   */
  function __construct($title = NULL, $action = CRM_Core_Action::NONE, $modal = TRUE) {
    parent::__construct($title, $modal, NULL, FALSE, TRUE);

    $mailingID = CRM_Utils_Request::retrieve('mid', 'String', $this, FALSE, NULL);

    // also get the text and html file
    $txtFile = CRM_Utils_Request::retrieve('txtFile', 'String',
      CRM_Core_DAO::$_nullObject, FALSE, NULL
    );

    $config = CRM_Core_Config::singleton();
    if ($txtFile &&
      file_exists($config->uploadDir . $txtFile)
    ) {
      $this->set('textFilePath', $config->uploadDir . $txtFile);
    }

    $this->_stateMachine = new CRM_SMS_StateMachine_Send($this, $action, $mailingID);

    // create and instantiate the pages
    $this->addPages($this->_stateMachine, $action);

    // add all the actions
    $uploadNames = array_merge(array('textFile'),
      CRM_Core_BAO_File::uploadNames()
    );

    $config = CRM_Core_Config::singleton();
    $this->addActions($config->uploadDir,
      $uploadNames
    );
  }
}

