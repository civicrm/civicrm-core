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
 * This class provides the common functionality for tasks that send emails.
 */
trait CRM_Contact_Form_Task_PDFTrait {

  /**
   * Set defaults for the pdf.
   *
   * @return array
   */
  public function setDefaultValues(): array {
    return $this->getPDFDefaultValues();
  }

  /**
   * Set default values.
   */
  protected function getPDFDefaultValues(): array {
    $defaultFormat = CRM_Core_BAO_PdfFormat::getDefaultValues();
    $defaultFormat['format_id'] = $defaultFormat['id'];
    return $defaultFormat;
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $this->addPDFElementsToForm();
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function addPDFElementsToForm(): void {
    $form = $this;
    // This form outputs a file so should never be submitted via ajax
    $form->preventAjaxSubmit();

    //Added for CRM-12682: Add activity subject and campaign fields
    CRM_Campaign_BAO_Campaign::addCampaign($form);
    $form->add(
      'text',
      'subject',
      ts('Activity Subject'),
      ['size' => 45, 'maxlength' => 255],
      FALSE
    );

    // Added for core#2121,
    // To support sending a custom pdf filename before downloading.
    $form->addElement('hidden', 'pdf_file_name');

    $form->addSelect('format_id', [
      'label' => ts('Select Format'),
      'placeholder' => ts('Default'),
      'entity' => 'message_template',
      'field' => 'pdf_format_id',
      'option_url' => 'civicrm/admin/pdfFormats',
    ]);
    $form->add(
      'select',
      'paper_size',
      ts('Paper Size'),
      [0 => ts('- default -')] + CRM_Core_BAO_PaperSize::getList(TRUE),
      FALSE,
      ['onChange' => 'selectPaper( this.value ); showUpdateFormatChkBox();']
    );
    $form->add(
      'select',
      'orientation',
      ts('Orientation'),
      CRM_Core_BAO_PdfFormat::getPageOrientations(),
      FALSE,
      ['onChange' => 'updatePaperDimensions(); showUpdateFormatChkBox();']
    );
    $form->add(
      'select',
      'metric',
      ts('Unit of Measure'),
      CRM_Core_BAO_PdfFormat::getUnits(),
      FALSE,
      ['onChange' => "selectMetric( this.value );"]
    );
    $form->add(
      'text',
      'margin_left',
      ts('Left Margin'),
      ['size' => 8, 'maxlength' => 8, 'onkeyup' => "showUpdateFormatChkBox();"],
      TRUE
    );
    $form->add(
      'text',
      'margin_right',
      ts('Right Margin'),
      ['size' => 8, 'maxlength' => 8, 'onkeyup' => "showUpdateFormatChkBox();"],
      TRUE
    );
    $form->add(
      'text',
      'margin_top',
      ts('Top Margin'),
      ['size' => 8, 'maxlength' => 8, 'onkeyup' => "showUpdateFormatChkBox();"],
      TRUE
    );
    $form->add(
      'text',
      'margin_bottom',
      ts('Bottom Margin'),
      ['size' => 8, 'maxlength' => 8, 'onkeyup' => "showUpdateFormatChkBox();"],
      TRUE
    );

    $config = CRM_Core_Config::singleton();
    /** CRM-15883 Suppressing Stationery path field until we switch from DOMPDF to a library that supports it.
     * if ($config->wkhtmltopdfPath == FALSE) {
     * $form->add(
     * 'text',
     * 'stationery',
     * ts('Stationery (relative path to PDF you wish to use as the background)'),
     * array('size' => 25, 'maxlength' => 900, 'onkeyup' => "showUpdateFormatChkBox();"),
     * FALSE
     * );
     * }
     */
    $form->add('checkbox', 'bind_format', ts('Always use this Page Format with the selected Template'));
    $form->add('checkbox', 'update_format', ts('Update Page Format (this will affect all templates that use this format)'));

    $form->assign('useThisPageFormat', ts('Always use this Page Format with the new template?'));
    $form->assign('useSelectedPageFormat', ts('Should the new template always use the selected Page Format?'));
    $form->assign('totalSelectedContacts', !is_null($form->_contactIds) ? count($form->_contactIds) : 0);

    $form->add('select', 'document_type', ts('Document Type'), CRM_Core_SelectValues::documentFormat());

    $documentTypes = implode(',', CRM_Core_SelectValues::documentApplicationType());
    $form->addElement('file', "document_file", 'Upload Document', 'size=30 maxlength=255 accept="' . $documentTypes . '"');
    $form->addUploadElement("document_file");

    CRM_Mailing_BAO_Mailing::commonCompose($form);

    $buttons = $this->getButtons($form);
    $form->addButtons($buttons);

    $form->addFormRule(['CRM_Core_Form_Task_PDFLetterCommon', 'formRule'], $form);
  }

  /**
   * Prepare form.
   */
  public function preProcessPDF(): void {
    $form = $this;
    $defaults = [];
    $form->_fromEmails = CRM_Core_BAO_Email::getFromEmail();
    if (is_numeric(key($form->_fromEmails))) {
      $emailID = (int) key($form->_fromEmails);
      $defaults = CRM_Core_BAO_Email::getEmailSignatureDefaults($emailID);
    }
    if (!Civi::settings()->get('allow_mail_from_logged_in_contact')) {
      $defaults['from_email_address'] = current(CRM_Core_BAO_Domain::getNameAndEmail(FALSE, TRUE));
    }
    $form->setDefaults($defaults);
    $form->setTitle(ts('Print/Merge Document'));
  }

  /**
   * Returns the filename for the pdf by striping off unwanted characters and limits the length to 200 characters.
   *
   * @return string
   *   The name of the file.
   */
  public function getFileName(): string {
    if (!empty($this->getSubmittedValue('pdf_file_name'))) {
      $fileName = CRM_Utils_File::makeFilenameWithUnicode($this->getSubmittedValue('pdf_file_name'), '_', 200);
    }
    elseif (!empty($this->getSubmittedValue('subject'))) {
      $fileName = CRM_Utils_File::makeFilenameWithUnicode($this->getSubmittedValue('subject'), '_', 200);
    }
    else {
      $fileName = 'CiviLetter';
    }
    return $this->isLiveMode() ? $fileName : $fileName . '_preview';
  }

  /**
   * Is the form in live mode (as opposed to being run as a preview).
   *
   * Returns true if the user has clicked the Download Document button on a
   * Print/Merge Document (PDF Letter) search task form, or false if the Preview
   * button was clicked.
   *
   * @return bool
   *   TRUE if the Download Document button was clicked (also defaults to TRUE
   *     if the form controller does not exist), else FALSE
   */
  protected function isLiveMode(): bool {
    return strpos($this->controller->getButtonName(), '_preview') === FALSE;
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess(): void {
    $formValues = $this->controller->exportValues($this->getName());
    [$formValues, $html_message] = $this->processMessageTemplate($formValues);
    $html = $activityIds = [];

    // CRM-16725 Skip creation of activities if user is previewing their PDF letter(s)
    if ($this->isLiveMode()) {
      $activityIds = $this->createActivities($html_message, $this->_contactIds, $formValues['subject'], CRM_Utils_Array::value('campaign_id', $formValues));
    }

    if (!empty($formValues['document_file_path'])) {
      [$html_message, $zip] = CRM_Utils_PDF_Document::unzipDoc($formValues['document_file_path'], $formValues['document_type']);
    }

    foreach ($this->getRows() as $row) {
      $tokenHtml = CRM_Core_BAO_MessageTemplate::renderTemplate([
        'contactId' => $row['contact_id'],
        'messageTemplate' => ['msg_html' => $html_message],
        'tokenContext' => array_merge(['schema' => $this->getTokenSchema()], ($row['schema'] ?? [])),
        'disableSmarty' => (!defined('CIVICRM_MAIL_SMARTY') || !CIVICRM_MAIL_SMARTY),
      ])['html'];

      $html[] = $tokenHtml;
    }

    $tee = NULL;
    if ($this->isLiveMode() && Civi::settings()->get('recordGeneratedLetters') === 'combined-attached') {
      if (count($activityIds) !== 1) {
        throw new CRM_Core_Exception('When recordGeneratedLetters=combined-attached, there should only be one activity.');
      }
      $tee = CRM_Utils_ConsoleTee::create()->start();
    }

    $type = $formValues['document_type'];
    $mimeType = $this->getMimeType($type);
    // ^^ Useful side-effect: consistently throws error for unrecognized types.

    $fileName = $this->getFileName();

    if ($type === 'pdf') {
      CRM_Utils_PDF_Utils::html2pdf($html, $fileName . '.pdf', FALSE, $formValues);
    }
    elseif (!empty($formValues['document_file_path'])) {
      $fileName = pathinfo($formValues['document_file_path'], PATHINFO_FILENAME) . '.' . $type;
      CRM_Utils_PDF_Document::printDocuments($html, $fileName, $type, $zip);
    }
    else {
      CRM_Utils_PDF_Document::html2doc($html, $fileName . '.' . $this->getSubmittedValue('document_type'), $formValues);
    }

    if ($tee) {
      $tee->stop();
      $content = file_get_contents($tee->getFileName(), NULL, NULL, NULL, 5);
      if (empty($content)) {
        throw new \CRM_Core_Exception("Failed to capture document content (type=$type)!");
      }
      foreach ($activityIds as $activityId) {
        civicrm_api3('Attachment', 'create', [
          'entity_table' => 'civicrm_activity',
          'entity_id' => $activityId,
          'name' => $fileName . '.' . $type,
          'mime_type' => $mimeType,
          'options' => [
            'move-file' => $tee->getFileName(),
          ],
        ]);
      }
    }

    $this->postProcessHook();

    CRM_Utils_System::civiExit();
  }

  /**
   * Convert from a vague-type/file-extension to mime-type.
   *
   * @param string $type
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected function getMimeType($type) {
    $mimeTypes = [
      'pdf' => 'application/pdf',
      'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'odt' => 'application/vnd.oasis.opendocument.text',
      'html' => 'text/html',
    ];
    if (isset($mimeTypes[$type])) {
      return $mimeTypes[$type];
    }
    else {
      throw new \CRM_Core_Exception("Cannot determine mime type");
    }
  }

  /**
   * @param string $html_message
   * @param array $contactIds
   * @param string $subject
   * @param int $campaign_id
   * @param array $perContactHtml
   *
   * @return array
   *   List of activity IDs.
   *   There may be 1 or more, depending on the system-settings
   *   and use-case.
   *
   * @throws \CRM_Core_Exception
   */
  protected function createActivities($html_message, $contactIds, $subject, $campaign_id, $perContactHtml = []): array {
    $activityParams = [
      'subject' => $subject,
      'campaign_id' => $campaign_id,
      'source_contact_id' => CRM_Core_Session::getLoggedInContactID(),
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Print PDF Letter'),
      'activity_date_time' => date('YmdHis'),
      'details' => $html_message,
    ];
    if (!empty($this->_activityId)) {
      $activityParams += ['id' => $this->_activityId];
    }

    $activityIds = [];
    switch (Civi::settings()->get('recordGeneratedLetters')) {
      case 'none':
        return [];

      case 'multiple':
        // One activity per contact.
        foreach ($contactIds as $i => $contactId) {
          $fullParams = ['target_contact_id' => $contactId] + $activityParams;
          if (!empty($this->_caseId)) {
            $fullParams['case_id'] = $this->_caseId;
          }
          elseif (!empty($this->_caseIds[$i])) {
            $fullParams['case_id'] = $this->_caseIds[$i];
          }

          if (isset($perContactHtml[$contactId])) {
            $fullParams['details'] = implode('<hr>', $perContactHtml[$contactId]);
          }
          $activity = civicrm_api3('Activity', 'create', $fullParams);
          $activityIds[$contactId] = $activity['id'];
        }

        break;

      case 'combined':
      case 'combined-attached':
        // One activity with all contacts.
        $fullParams = ['target_contact_id' => $contactIds] + $activityParams;
        if (!empty($this->_caseId)) {
          $fullParams['case_id'] = $this->_caseId;
        }
        elseif (!empty($this->_caseIds)) {
          $fullParams['case_id'] = $this->_caseIds;
        }
        $activity = civicrm_api3('Activity', 'create', $fullParams);
        $activityIds[] = $activity['id'];
        break;

      default:
        throw new CRM_Core_Exception('Unrecognized option in recordGeneratedLetters: ' . Civi::settings()->get('recordGeneratedLetters'));
    }

    return $activityIds;
  }

  /**
   * Handle the template processing part of the form
   *
   * @param array $formValues
   *
   * @return string $html_message
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function processTemplate(&$formValues) {
    $html_message = $formValues['html_message'] ?? NULL;

    // process message template
    if (!empty($this->getSubmittedValue('saveTemplate')) || !empty($formValues['updateTemplate'])) {
      $messageTemplate = [
        'msg_text' => NULL,
        'msg_html' => $formValues['html_message'],
        'msg_subject' => NULL,
        'is_active' => TRUE,
      ];

      $messageTemplate['pdf_format_id'] = 'null';
      if (!empty($formValues['bind_format']) && $formValues['format_id']) {
        $messageTemplate['pdf_format_id'] = $formValues['format_id'];
      }
      if ($this->getSubmittedValue('saveTemplate')) {
        $messageTemplate['msg_title'] = $this->getSubmittedValue('saveTemplateName');
        CRM_Core_BAO_MessageTemplate::add($messageTemplate);
      }

      if ($formValues['template'] && !empty($formValues['updateTemplate'])) {
        $messageTemplate['id'] = $formValues['template'];

        unset($messageTemplate['msg_title']);
        CRM_Core_BAO_MessageTemplate::add($messageTemplate);
      }
    }
    elseif (CRM_Utils_Array::value('template', $formValues) > 0) {
      if (!empty($formValues['bind_format']) && $formValues['format_id']) {
        $query = "UPDATE civicrm_msg_template SET pdf_format_id = {$formValues['format_id']} WHERE id = {$formValues['template']}";
      }
      else {
        $query = "UPDATE civicrm_msg_template SET pdf_format_id = NULL WHERE id = {$formValues['template']}";
      }
      CRM_Core_DAO::executeQuery($query);

      $documentInfo = CRM_Core_BAO_File::getEntityFile('civicrm_msg_template', $formValues['template']);
      if ($documentInfo) {
        $info = reset($documentInfo);
        [$html_message, $formValues['document_type']] = CRM_Utils_PDF_Document::docReader($info['fullPath'], $info['mime_type']);
        $formValues['document_file_path'] = $info['fullPath'];
      }
    }
    // extract the content of uploaded document file
    elseif (!empty($formValues['document_file'])) {
      [$html_message, $formValues['document_type']] = CRM_Utils_PDF_Document::docReader($formValues['document_file']['name'], $formValues['document_file']['type']);
      $formValues['document_file_path'] = $formValues['document_file']['name'];
    }

    if (!empty($formValues['update_format'])) {
      $bao = new CRM_Core_BAO_PdfFormat();
      $bao->savePdfFormat($formValues, $formValues['format_id']);
    }

    return $html_message;
  }

  /**
   * Part of the post process which prepare and extract information from the template.
   *
   *
   * @param array $formValues
   *
   * @return array
   *   [$categories, $html_message, $messageToken, $returnProperties]
   */
  public function processMessageTemplate($formValues) {
    $html_message = $this->processTemplate($formValues);

    //time being hack to strip '&nbsp;'
    //from particular letter line, CRM-6798
    $this->formatMessage($html_message);

    $messageToken = CRM_Utils_Token::getTokens($html_message);

    $returnProperties = [];
    if (isset($messageToken['contact'])) {
      foreach ($messageToken['contact'] as $key => $value) {
        $returnProperties[$value] = 1;
      }
    }

    return [$formValues, $html_message, $messageToken, $returnProperties];
  }

  /**
   * @param $message
   */
  public function formatMessage(&$message) {
    $newLineOperators = [
      'p' => [
        'oper' => '<p>',
        'pattern' => '/<(\s+)?p(\s+)?>/m',
      ],
      'br' => [
        'oper' => '<br />',
        'pattern' => '/<(\s+)?br(\s+)?\/>/m',
      ],
    ];

    $htmlMsg = preg_split($newLineOperators['p']['pattern'], $message);
    foreach ($htmlMsg as $k => & $m) {
      $messages = preg_split($newLineOperators['br']['pattern'], $m);
      foreach ($messages as $key => & $msg) {
        $msg = trim($msg);
        $matches = [];
        if (preg_match('/^(&nbsp;)+/', $msg, $matches)) {
          $spaceLen = strlen($matches[0]) / 6;
          $trimMsg = ltrim($msg, '&nbsp; ');
          $charLen = strlen($trimMsg);
          $totalLen = $charLen + $spaceLen;
          if ($totalLen > 100) {
            $spacesCount = 10;
            if ($spaceLen > 50) {
              $spacesCount = 20;
            }
            if ($charLen > 100) {
              $spacesCount = 1;
            }
            $msg = str_repeat('&nbsp;', $spacesCount) . $trimMsg;
          }
        }
      }
      $m = implode($newLineOperators['br']['oper'], $messages);
    }
    $message = implode($newLineOperators['p']['oper'], $htmlMsg);
  }

  /**
   * Get the buttons to display.
   *
   * @return array
   */
  protected function getButtons(): array {
    $buttons = [];
    if (!$this->isFormInViewMode()) {
      $buttons[] = [
        'type' => 'upload',
        'name' => $this->getMainSubmitButtonName(),
        'isDefault' => TRUE,
        'icon' => 'fa-download',
      ];
      $buttons[] = [
        'type' => 'submit',
        'name' => ts('Preview'),
        'subName' => 'preview',
        'icon' => 'fa-search',
        'isDefault' => FALSE,
      ];
    }
    $buttons[] = [
      'type' => 'cancel',
      'name' => $this->isFormInViewMode() ? ts('Done') : ts('Cancel'),
    ];
    return $buttons;
  }

  /**
   * Get the name for the main submit button.
   *
   * @return string
   */
  protected function getMainSubmitButtonName(): string {
    return ts('Download Document');
  }

}
