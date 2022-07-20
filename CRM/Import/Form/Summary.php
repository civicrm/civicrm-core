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
 *
 * TODO: CRM-11254 - if preProcess and postProcess functions can be reconciled between the 5 child classes,
 * those classes can be removed entirely and this class will not need to be abstract
 */
abstract class CRM_Import_Form_Summary extends CRM_Import_Forms {

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {
    $this->assignOutputURLs();
  }

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string
   */
  public function getTitle() {
    return ts('Summary');
  }

  protected function assignOutputURLs(): void {
    $this->assign('outputUnavailable', FALSE);
    try {
      $this->assign('totalRowCount', $this->getRowCount());
      $this->assign('validRowCount', $this->getRowCount(CRM_Import_Parser::VALID) + $this->getRowCount(CRM_Import_Parser::UNPARSED_ADDRESS_WARNING));
      $this->assign('invalidRowCount', $this->getRowCount(CRM_Import_Parser::ERROR));
      $this->assign('duplicateRowCount', $this->getRowCount(CRM_Import_Parser::DUPLICATE));
      $this->assign('unMatchCount', $this->getRowCount(CRM_Import_Parser::NO_MATCH));
      $this->assign('validSoftCreditRowCount', $this->getRowCount(CRM_Contribute_Import_Parser_Contribution::SOFT_CREDIT));
      $this->assign('invalidSoftCreditRowCount', $this->getRowCount(CRM_Contribute_Import_Parser_Contribution::SOFT_CREDIT_ERROR));
      $this->assign('validPledgePaymentRowCount', $this->getRowCount(CRM_Contribute_Import_Parser_Contribution::PLEDGE_PAYMENT));
      $this->assign('invalidPledgePaymentRowCount', $this->getRowCount(CRM_Contribute_Import_Parser_Contribution::PLEDGE_PAYMENT_ERROR));
      $this->assign('unparsedAddressCount', $this->getRowCount(CRM_Import_Parser::UNPARSED_ADDRESS_WARNING));
      $this->assign('downloadDuplicateRecordsUrl', $this->getDownloadURL(CRM_Import_Parser::DUPLICATE));
      $this->assign('downloadErrorRecordsUrl', $this->getDownloadURL(CRM_Import_Parser::ERROR));
      $this->assign('downloadMismatchRecordsUrl', $this->getDownloadURL(CRM_Import_Parser::NO_MATCH));
      $this->assign('downloadAddressRecordsUrl', $this->getDownloadURL(CRM_Import_Parser::UNPARSED_ADDRESS_WARNING));
      $this->assign('downloadPledgePaymentErrorRecordsUrl', $this->getDownloadURL(CRM_Contribute_Import_Parser_Contribution::PLEDGE_PAYMENT_ERROR));
      $this->assign('downloadSoftCreditErrorRecordsUrl', $this->getDownloadURL(CRM_Contribute_Import_Parser_Contribution::SOFT_CREDIT_ERROR));
      $this->assign('trackingSummary', $this->getTrackingSummary());

      $userJobID = CRM_Utils_Request::retrieve('user_job_id', 'String', $this, TRUE);
      $userJob = UserJob::get(TRUE)
        ->addWhere('id', '=', $userJobID)
        ->execute()
        ->first();
      $onDuplicate = (int) $userJob['metadata']['submitted_values']['onDuplicate'];
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
    catch (CRM_Import_Exception_ImportTableUnavailable $e) {
      $this->assign('outputUnavailable', TRUE);
    }
  }

}
