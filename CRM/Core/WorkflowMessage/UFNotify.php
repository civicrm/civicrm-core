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

use Civi\WorkflowMessage\GenericWorkflowMessage;

/**
 * Receipt sent when someone receives a copy of profile that has been filled out.
 *
 * @method int getProfileID()
 * @method $this setProfileID(int $profileID)
 * @method array getProfileFields()
 * @method $this setProfileFields(array $profileFields)
 *
 * @support template-only
 *
 * @see CRM_Core_BAO_UFGroup::commonSendMail
 */
class CRM_Core_WorkflowMessage_UFNotify extends GenericWorkflowMessage {
  public const WORKFLOW = 'uf_notify';

  /**
   * @var int
   *
   * @scope tplParams
   */
  protected $profileID;

  /**
   * @var array
   *
   * @scope tplParams as values
   */
  protected $profileFields;

  /**
   * @var string
   *
   * @scope tplParams
   */
  protected $contactLink;

  public function getContactLink(): string {
    return CRM_Utils_System::url('civicrm/contact/view',
      "reset=1&cid=" . $this->getContactID(),
      TRUE, NULL, FALSE, FALSE, TRUE
    );
  }

  /**
   * @var string
   *
   * @scope tplParams as grouptitle
   */
  protected $groupTitle;

  public function getGroupTitle(): string {
    return CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->getProfileID(), 'frontend_title');
  }

  /**
   * @var string
   *
   * @scope tplParams
   */
  protected $userDisplayName;

  public function getUserDisplayName(): string {
    $loggedInUser = CRM_Core_Session::getLoggedInContactID();
    if (!$loggedInUser) {
      return '';
    }
    return CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
      $loggedInUser,
      'display_name'
    );
  }

}
