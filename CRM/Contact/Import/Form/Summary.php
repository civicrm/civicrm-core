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
class CRM_Contact_Import_Form_Summary extends CRM_Import_Forms {

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {
    $userJobID = CRM_Utils_Request::retrieve('user_job_id', 'String', $this, TRUE);
    $userJob = UserJob::get(TRUE)->addWhere('id', '=', $userJobID)->addSelect('metadata', 'job_type:label')->execute()->first();
    $this->setTitle($userJob['job_type:label']);
    $onDuplicate = (int) ($userJob['metadata']['submitted_values']['onDuplicate'] ?? 0);
    $this->assign('dupeError', FALSE);
    $importBaseURL = $this->getUserJobInfo()['url'] ?? NULL;
    $this->assign('templateURL', ($importBaseURL && $this->getTemplateID()) ? CRM_Utils_System::url($importBaseURL, ['template_id' => $this->getTemplateID(), 'reset' => 1]) : '');
    // This can be overridden by Civi-Import so that the Download url
    // links that go to SearchKit open in a new tab.
    $this->assign('isOpenResultsInNewTab');
    $this->assign('allRowsUrl');
    $this->assign('importedRowsUrl');

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

  /**
   * Assign the relevant smarty variables.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function assignOutputURLs(): void {
    $this->assign('outputUnavailable', FALSE);
    try {
      $this->assign('totalRowCount', $this->getRowCount());
      $this->assign('unprocessedRowCount', $this->getRowCount() - $this->getRowCount('imported') - $this->getRowCount(CRM_Import_Parser::ERROR) - $this->getRowCount(CRM_Import_Parser::DUPLICATE));
      $this->assign('importedRowCount', $this->getRowCount('imported'));
      $this->assign('invalidRowCount', $this->getRowCount(CRM_Import_Parser::ERROR));
      $this->assign('duplicateRowCount', $this->getRowCount(CRM_Import_Parser::DUPLICATE));
      $this->assign('unMatchCount', $this->getRowCount(CRM_Import_Parser::NO_MATCH));
      $this->assign('validSoftCreditRowCount', $this->getRowCount(CRM_Import_Parser::SOFT_CREDIT));
      $this->assign('invalidSoftCreditRowCount', $this->getRowCount(CRM_Import_Parser::SOFT_CREDIT_ERROR));
      $this->assign('validPledgePaymentRowCount', $this->getRowCount(CRM_Import_Parser::PLEDGE_PAYMENT));
      $this->assign('invalidPledgePaymentRowCount', $this->getRowCount(CRM_Import_Parser::PLEDGE_PAYMENT_ERROR));
      $this->assign('unparsedAddressCount', $this->getRowCount(CRM_Import_Parser::UNPARSED_ADDRESS_WARNING));
      $this->assign('downloadDuplicateRecordsUrl', $this->getDownloadURL(CRM_Import_Parser::DUPLICATE));
      $this->assign('downloadErrorRecordsUrl', $this->getDownloadURL(CRM_Import_Parser::ERROR));
      $this->assign('downloadMismatchRecordsUrl', $this->getDownloadURL(CRM_Import_Parser::NO_MATCH));
      $this->assign('downloadAddressRecordsUrl', $this->getDownloadURL(CRM_Import_Parser::UNPARSED_ADDRESS_WARNING));
      $this->assign('downloadPledgePaymentErrorRecordsUrl', $this->getDownloadURL(CRM_Import_Parser::PLEDGE_PAYMENT_ERROR));
      $this->assign('downloadSoftCreditErrorRecordsUrl', $this->getDownloadURL(CRM_Import_Parser::SOFT_CREDIT_ERROR));
      $this->assign('trackingSummary', $this->getTrackingSummary());

      $userJobID = CRM_Utils_Request::retrieve('user_job_id', 'String', $this, TRUE);
      $userJob = UserJob::get(TRUE)
        ->addWhere('id', '=', $userJobID)
        ->addSelect('*', 'status_id:name', 'status_id:label', 'search_display_id.name', 'search_display_id.saved_search_id.name')
        ->execute()
        ->first();
      $this->assign('statusName', $userJob['status_id:name']);
      $this->assign('statusLabel', $userJob['status_id:label']);
      $searchDisplayLink = '';
      // If this is a SearchKit batch, add a link to get back to the search display.
      if (!empty($userJob['search_display_id.name'])) {
        $searchDisplayLink = \Civi::url('backend://civicrm/search')
          ->setFragment("display/{$userJob['search_display_id.saved_search_id.name']}/{$userJob['search_display_id.name']}?batch={$userJobID}");
      }
      $this->assign('searchDisplayLink', (string) $searchDisplayLink);
      $onDuplicate = (int) ($userJob['metadata']['submitted_values']['onDuplicate'] ?? 0);
      $this->assign('dupeError', FALSE);
      if ($onDuplicate === CRM_Import_Parser::DUPLICATE_UPDATE) {
        $dupeActionString = ts('These records have been updated with the imported data.');
      }
      elseif ($onDuplicate === CRM_Import_Parser::DUPLICATE_FILL) {
        $dupeActionString = ts('These records have been filled in with the imported data.');
      }
      else {
        // Skip by default.
        $dupeActionString = ts('These records have not been imported.');
        $this->assign('dupeError', TRUE);
      }
      $this->assign('dupeActionString', $dupeActionString);
    }
    // @todo - remove this - it is never thrown.
    catch (CRM_Import_Exception_ImportTableUnavailable $e) {
      $this->assign('outputUnavailable', TRUE);
    }
  }

}
