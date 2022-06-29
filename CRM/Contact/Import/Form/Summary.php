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

use Civi\Api4\UserJob;

/**
 * This class summarizes the import results.
 */
class CRM_Contact_Import_Form_Summary extends CRM_Import_Form_Summary {

  /**
   * Set variables up before form is built.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $userJobID = CRM_Utils_Request::retrieve('user_job_id', 'String', $this, TRUE);
    $userJob = UserJob::get(TRUE)->addWhere('id', '=', $userJobID)->addSelect('metadata', 'job_type:label')->execute()->first();
    $this->setTitle($userJob['job_type:label']);
    $onDuplicate = $userJob['metadata']['submitted_values']['onDuplicate'];
    $this->assign('dupeError', FALSE);

    if ($onDuplicate === CRM_Import_Parser::DUPLICATE_UPDATE) {
      $this->assign('dupeActionString', ts('These records have been updated with the imported data.'));
    }
    elseif ($onDuplicate === CRM_Import_Parser::DUPLICATE_FILL) {
      $this->assign('dupeActionString', ts('These records have been filled in with the imported data.'));
    }
    else {
      /* Skip by default */
      $this->assign('dupeActionString', ts('These records have not been imported.'));
      $this->assign('dupeError', TRUE);
    }

    $this->assign('groupAdditions', $this->getUserJob()['metadata']['summary_info']['groups'] ?? []);
    $this->assign('tagAdditions', $this->getUserJob()['metadata']['summary_info']['tags'] ?? []);
    $this->assignOutputURLs();
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/import/contact', 'reset=1'));
  }

}
