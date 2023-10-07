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
 * Back office participant form.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Back office participant delete form.
 */
class CRM_Event_Form_Participant_Delete extends CRM_Core_Form {
  use CRM_Event_Form_EventFormTrait;
  use CRM_Contact_Form_ContactFormTrait;

  /**
   * @var int
   */
  private $participantID;

  /**
   * Get id of participant being edited.
   *
   * @return int|null
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * No exception is thrown as abort is not TRUE.
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getParticipantID(): ?int {
    if ($this->participantID === NULL) {
      $id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
      $this->participantID = $id ? (int) $id : FALSE;
    }
    return $this->participantID ?: NULL;
  }

  /**
   * Get the selected Event ID.
   *
   * @return int|null
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getEventID(): ?int {
    return $this->getParticipantValue('event_id');
  }

  /**
   * Get the relevant contact ID.
   *
   * @return int|null
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getContactID(): ?int {
    return $this->getParticipantValue('contact_id');
  }

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {
    $this->setAction(CRM_Core_Action::DELETE);
    $this->setTitle(ts('Delete participant record for %1', [1 => $this->getContactValue('display_name')]));
    $contributionID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment',
      $this->getParticipantID(), 'contribution_id', 'participant_id'
    );
    if ($contributionID && !CRM_Core_Permission::checkActionPermission('CiviContribute', CRM_Core_Action::DELETE)) {
      CRM_Core_Error::statusBounce(ts("This Participant is linked to a contribution. You must have 'delete in CiviContribute' permission in order to delete this record."));
    }
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $additionalParticipant = (int) CRM_Core_DAO::singleValueQuery(
      'SELECT count(*) FROM civicrm_participant WHERE registered_by_id = %1 AND id <> registered_by_id',
      [1 => [$this->getParticipantID(), 'Integer']]
    );
    if ($additionalParticipant) {
      $deleteParticipants = [
        1 => ts('Delete this participant record along with associated participant record(s).'),
        2 => ts('Delete only this participant record.'),
      ];
      $this->addRadio('delete_participant', NULL, $deleteParticipants, NULL, '<br />');
      $this->setDefaults(['delete_participant' => 1]);
    }
    $this->assign('additionalParticipant', $additionalParticipant);
    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Delete'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  /**
   * Process the form submission.
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function postProcess(): void {
    $deleteParticipantOption = (int) $this->getSubmittedValue('delete_participant');
    if ($deleteParticipantOption === 2) {
      $additionalID = (CRM_Event_BAO_Participant::getAdditionalParticipantIds($this->getParticipantID()));
      $participantLinks = (CRM_Event_BAO_Participant::getAdditionalParticipantUrl($additionalID));
    }
    if ($deleteParticipantOption === 1) {
      $additionalIDs = CRM_Event_BAO_Participant::getAdditionalParticipantIds($this->getParticipantID());
      foreach ($additionalIDs as $value) {
        CRM_Event_BAO_Participant::deleteParticipant($value);
      }
    }
    CRM_Event_BAO_Participant::deleteParticipant($this->getParticipantID());
    CRM_Core_Session::setStatus(ts('Selected participant was deleted successfully.'), ts('Record Deleted'), 'success');
    if (!empty($participantLinks)) {
      $status = ts('The following participants no longer have an event fee recorded. You can edit their registration and record a replacement contribution by clicking the links below:') . '<br/>' . $participantLinks;
      CRM_Core_Session::setStatus($status, ts('Group Payment Deleted'));
    }
  }

}
