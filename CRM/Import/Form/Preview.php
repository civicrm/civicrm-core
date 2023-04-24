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
 * This class previews the uploaded file and returns summary statistics.
 *
 * TODO: CRM-11254 - if preProcess and postProcess functions can be reconciled between the 5 child classes,
 * those classes can be removed entirely and this class will not need to be abstract
 */
abstract class CRM_Import_Form_Preview extends CRM_Import_Forms {

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string
   */
  public function getTitle() {
    return ts('Preview');
  }

  /**
   * Assign common values to the template.
   */
  public function preProcess() {
    $this->assignPreviewVariables();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addButtons($this->getButtons());
  }

  /**
   * Set status url for ajax.
   */
  public function setStatusUrl() {
    $statusID = $this->get('statusID');
    if (!$statusID) {
      $statusID = md5(uniqid(rand(), TRUE));
      $this->set('statusID', $statusID);
    }
    $statusUrl = CRM_Utils_System::url('civicrm/ajax/status', "id={$statusID}", FALSE, NULL, FALSE);
    $this->assign('statusUrl', $statusUrl);
  }

  /**
   * Assign smarty variables for the preview screen.
   *
   * @throws \CRM_Core_Exception
   */
  protected function assignPreviewVariables(): void {
    $this->assign('downloadErrorRecordsUrl', $this->getDownloadURL(CRM_Import_Parser::ERROR));
    $this->assign('invalidRowCount', $this->getRowCount(CRM_Import_Parser::ERROR));
    $this->assign('validRowCount', $this->getRowCount(CRM_Import_Parser::VALID));
    $this->assign('totalRowCount', $this->getRowCount([]));
    $this->assign('mapper', $this->getMappedFieldLabels());
    $this->assign('dataValues', $this->getDataRows([], 2));
    $this->assign('columnNames', $this->getColumnHeaders());
    // This can be overridden by Civi-Import so that the Download url
    // links that go to SearchKit open in a new tab.
    $this->assign('isOpenResultsInNewTab');
    $this->assign('allRowsUrl');
    //get the mapping name displayed if the mappingId is set
    $mappingId = $this->get('loadMappingId');
    if ($mappingId) {
      $mapDAO = new CRM_Core_DAO_Mapping();
      $mapDAO->id = $mappingId;
      $mapDAO->find(TRUE);
    }
    $this->assign('savedMappingName', $mappingId ? $mapDAO->name : NULL);
    $this->assign('skipColumnHeader', $this->getSubmittedValue('skipColumnHeader'));
    $this->assign('showColumnNames', $this->getSubmittedValue('skipColumnHeader'));
    // rowDisplayCount is deprecated - it used to be used with {section} but we have nearly gotten rid of it.
    $this->assign('rowDisplayCount', $this->getSubmittedValue('skipColumnHeader') ? 3 : 2);
  }

  /**
   * Process the mapped fields and map it into the uploaded file
   * preview the file and extract some summary statistics
   *
   * @return void
   */
  public function postProcess() {
    $this->runTheImport();
  }

  /**
   * Run the import.
   *
   * @throws \CRM_Core_Exception
   */
  protected function runTheImport(): void {
    $parser = $this->getParser();
    $parser->queue();
    $queue = Civi::queue('user_job_' . $this->getUserJobID());
    $runner = new CRM_Queue_Runner([
      'queue' => $queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
      'onEndUrl' => CRM_Utils_System::url('civicrm/import/contact/summary', [
        'user_job_id' => $this->getUserJobID(),
        'reset' => 1,
      ], FALSE, NULL, FALSE),
    ]);
    $runner->runAllInteractive();
  }

  /**
   * Get the buttons for the form.
   *
   * @return array|array[]
   * @throws \CRM_Core_Exception
   */
  private function getButtons(): array {
    // FIXME: This is a hack...
    // The tpl contains javascript that starts the import on form submit
    // Since our back/cancel buttons are of html type "submit" we have to prevent a form submit event when they are clicked
    // Hacking in some onclick js to make them act more like links instead of buttons
    $path = CRM_Utils_System::currentPath();
    $query = ['_qf_MapField_display' => 'true'];
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String');
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $query['qfKey'] = $qfKey;
    }
    $previousURL = CRM_Utils_System::url($path, $query, FALSE, NULL, FALSE);
    $cancelURL = CRM_Utils_System::url($path, 'reset=1', FALSE, NULL, FALSE);
    $buttons = [
      [
        'type' => 'back',
        'name' => ts('Previous'),
        'js' => ['onclick' => "location.href='{$previousURL}'; return false;"],
      ],
    ];
    if ($this->hasImportableRows()) {
      $buttons[] = [
        'type' => 'next',
        'name' => ts('Import Now'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ];
    }
    $buttons[] = [
      'type' => 'cancel',
      'name' => ts('Cancel'),
      'js' => ['onclick' => "location.href='{$cancelURL}'; return false;"],
    ];

    return $buttons;
  }

}
