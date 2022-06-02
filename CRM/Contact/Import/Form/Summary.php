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
    // @todo - totally unclear that this errorFile could ever be set / render.
    // Probably it can go.
    $this->assign('errorFile', $this->get('errorFile'));
    $onDuplicate = $this->getSubmittedValue('onDuplicate');
    $this->assign('dupeError', FALSE);

    if ($onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE) {
      $this->assign('dupeActionString', ts('These records have been updated with the imported data.'));
    }
    elseif ($onDuplicate == CRM_Import_Parser::DUPLICATE_FILL) {
      $this->assign('dupeActionString', ts('These records have been filled in with the imported data.'));
    }
    else {
      /* Skip by default */
      $this->assign('dupeActionString', ts('These records have not been imported.'));
      $this->assign('dupeError', TRUE);
    }

    $this->assign('groupAdditions', $this->getUserJob()['metadata']['summary_info']['groups']);
    $this->assign('tagAdditions', $this->getUserJob()['metadata']['summary_info']['tags']);
    $this->assign('totalRowCount', $this->getRowCount());
    $this->assign('validRowCount', $this->getRowCount(CRM_Import_Parser::VALID) + $this->getRowCount(CRM_Import_Parser::UNPARSED_ADDRESS_WARNING));
    $this->assign('invalidRowCount', $this->getRowCount(CRM_Import_Parser::ERROR));
    $this->assign('duplicateRowCount', $this->getRowCount(CRM_Import_Parser::DUPLICATE));
    $this->assign('unMatchCount', $this->getRowCount(CRM_Import_Parser::NO_MATCH));
    $this->assign('unparsedAddressCount', $this->getRowCount(CRM_Import_Parser::UNPARSED_ADDRESS_WARNING));
    $this->assign('downloadDuplicateRecordsUrl', $this->getDownloadURL(CRM_Import_Parser::DUPLICATE));
    $this->assign('downloadErrorRecordsUrl', $this->getDownloadURL(CRM_Import_Parser::ERROR));
    $this->assign('downloadMismatchRecordsUrl', $this->getDownloadURL(CRM_Import_Parser::NO_MATCH));
    $this->assign('downloadAddressRecordsUrl', $this->getDownloadURL(CRM_Import_Parser::UNPARSED_ADDRESS_WARNING));
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/import/contact', 'reset=1'));
  }

  /**
   * Clean up the import table we used.
   */
  public function postProcess() {
  }

}
