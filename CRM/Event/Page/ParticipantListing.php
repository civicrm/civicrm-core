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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Event_Page_ParticipantListing extends CRM_Core_Page {

  protected $_id;

  protected $_participantListingID;

  protected $_eventTitle;

  protected $_pager;

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Integer', $this, TRUE);

    // ensure that there is a participant type for this
    $this->_participantListingID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event',
      $this->_id,
      'participant_listing_id'
    );
    if (!$this->_participantListingID) {
      CRM_Core_Error::statusBounce(ts('The Participant Listing feature is not currently enabled for this event.'));
    }

    // retrieve Event Title and include it in page title
    $this->_eventTitle = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event',
      $this->_id,
      'title'
    );
    CRM_Utils_System::setTitle(ts('%1 - Participants', [1 => $this->_eventTitle]));

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
      CRM_Core_Error::statusBounce(ts("Participant listing code file cannot be '%1'",
        [1 => $className]
      ));
    }

    $classFile = str_replace('_',
        DIRECTORY_SEPARATOR,
        $className
      ) . '.php';
    $error = include_once $classFile;
    if ($error == FALSE) {
      CRM_Core_Error::statusBounce(ts('Participant listing code file: %1 does not exist. Please verify your custom participant listing settings in CiviCRM administrative panel.', [1 => $classFile]));
    }

    $participantListingClass = new $className();

    $participantListingClass->preProcess();
    $participantListingClass->run();
  }

}
