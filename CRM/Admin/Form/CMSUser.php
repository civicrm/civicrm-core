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

/**
 * This class generates form components for Synchronizing CMS Users
 */
class CRM_Admin_Form_CMSUser extends CRM_Core_Form {

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Disable on Standalone
   */
  public function preProcess() {
    if (!\CRM_Utils_System::allowSynchronizeUsers()) {
      \CRM_Core_Error::statusBounce(ts('This framework doesn\'t allow for syncing CMS users.'));
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('OK'),
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
   */
  public function postProcess() {
    $result = CRM_Utils_System::synchronizeUsersIfAllowed();

    $status = ts('Checked one user record.',
        [
          'count' => $result['contactCount'],
          'plural' => 'Checked %count user records.',
        ]
      );
    if ($result['contactMatching']) {
      $status .= '<br />' . ts('Found one matching contact record.',
          [
            'count' => $result['contactMatching'],
            'plural' => 'Found %count matching contact records.',
          ]
        );
    }

    $status .= '<br />' . ts('Created one new contact record.',
        [
          'count' => $result['contactCreated'],
          'plural' => 'Created %count new contact records.',
        ]
      );
    CRM_Core_Session::setStatus($status, ts('Synchronize Complete'), 'success');
    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));
  }

}
