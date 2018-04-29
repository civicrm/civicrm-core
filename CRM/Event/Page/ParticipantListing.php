<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 * $Id$
 *
 */
class CRM_Event_Page_ParticipantListing extends CRM_Core_Page {

  protected $_id;

  protected $_participantListingID;

  protected $_eventTitle;

  protected $_pager;

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Integer', $this, TRUE);

    // ensure that there is a particpant type for this
    $this->_participantListingID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event',
      $this->_id,
      'participant_listing_id'
    );
    if (!$this->_participantListingID) {
      CRM_Core_Error::fatal(ts('The Participant Listing feature is not currently enabled for this event.'));
    }

    // retrieve Event Title and include it in page title
    $this->_eventTitle = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event',
      $this->_id,
      'title'
    );
    CRM_Utils_System::setTitle(ts('%1 - Participants', array(1 => $this->_eventTitle)));

    // we do not want to display recently viewed contacts since this is potentially a public page
    $this->assign('displayRecent', FALSE);
  }

  /**
   * Run listing page.
   *
   * @throws \Exception
   */
  public function run() {
    $this->preProcess();

    // get the class name from the participantListingID
    $className = CRM_Utils_Array::value($this->_participantListingID,
      CRM_Core_PseudoConstant::get(
        'CRM_Event_BAO_Event',
        'participant_listing_id',
        ['keyColumn' => 'value', 'labelColumn' => 'description']
      )
    );
    if ($className == 'CRM_Event_Page_ParticipantListing') {
      CRM_Core_Error::fatal(ts("Participant listing code file cannot be '%1'",
        array(1 => $className)
      ));
    }

    $classFile = str_replace('_',
        DIRECTORY_SEPARATOR,
        $className
      ) . '.php';
    $error = include_once $classFile;
    if ($error == FALSE) {
      CRM_Core_Error::fatal('Participant listing code file: ' . $classFile . ' does not exist. Please verify your custom particpant listing settings in CiviCRM administrative panel.');
    }

    $participantListingClass = new $className();

    $participantListingClass->preProcess();
    $participantListingClass->run();
  }

}
