<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class provides the common functionality for creating PDF letter for one or a group of contact ids.
 */
class CRM_Contact_Form_Task_PDFLetterCommon {

  protected static $tokenCategories;

  /**
   * @return array
   *   Array(string $machineName => string $label).
   */
  public static function getLoggingOptions() {
    return array(
      'none' => ts('Do not record'),
      'multiple' => ts('Multiple activities (one per contact)'),
      'combined' => ts('One combined activity'),
      'combined-attached' => ts('One combined activity plus one file attachment'),
      // 'multiple-attached' <== not worth the work
    );
  }

  /**
   * Build all the data structures needed to build the form.
   *
   * @param CRM_Core_Form $form
   */
  public static function preProcess(&$form) {
    CRM_Contact_Form_Task_EmailCommon::preProcessFromAddress($form);
    $messageText = array();
    $messageSubject = array();
    $dao = new CRM_Core_BAO_MessageTemplate();
    $dao->is_active = 1;
    $dao->find();
    while ($dao->fetch()) {
      $messageText[$dao->id] = $dao->msg_text;
      $messageSubject[$dao->id] = $dao->msg_subject;
    }

    $form->assign('message', $messageText);
    $form->assign('messageSubject', $messageSubject);
    CRM_Utils_System::setTitle('Print/Merge Document');
  }

  /**
   * @param CRM_Core_Form $form
   * @param int $cid
   */
  public static function preProcessSingle(&$form, $cid) {
    $form->_contactIds = explode(',', $cid);
    // put contact display name in title for single contact mode
    if (count($form->_contactIds) === 1) {
      CRM_Utils_System::setTitle(ts('Print/Merge Document for %1', array(1 => CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid, 'display_name'))));
    }
  }

  /**
   * Build the form object.
   *
   * @var CRM_Core_Form $form
   */
  public static function buildQuickForm(&$form) {
    // This form outputs a file so should never be submitted via ajax
    $form->preventAjaxSubmit();

    //Added for CRM-12682: Add activity subject and campaign fields
    CRM_Campaign_BAO_Campaign::addCampaign($form);
    $form->add(
      'text',
      'subject',
      ts('Activity Subject'),
      array('size' => 45, 'maxlength' => 255),
      FALSE
    );

    $form->add('static', 'pdf_format_header', NULL, ts('Page Format: %1', array(1 => '<span class="pdf-format-header-label"></span>')));
    $form->addSelect('format_id', array(
      'label' => ts('Select Format'),
      'placeholder' => ts('Default'),
      'entity' => 'message_template',
      'field' => 'pdf_format_id',
      'option_url' => 'civicrm/admin/pdfFormats',
    ));
    $form->add(
      'select',
      'paper_size',
      ts('Paper Size'),
      array(0 => ts('- default -')) + CRM_Core_BAO_PaperSize::getList(TRUE),
      FALSE,
      array('onChange' => "selectPaper( this.value ); showUpdateFormatChkBox();")
    );
    $form->add('static', 'paper_dimensions', NULL, ts('Width x Height'));
    $form->add(
      'select',
      'orientation',
      ts('Orientation'),
      CRM_Core_BAO_PdfFormat::getPageOrientations(),
      FALSE,
      array('onChange' => "updatePaperDimensions(); showUpdateFormatChkBox();")
    );
    $form->add(
      'select',
      'metric',
      ts('Unit of Measure'),
      CRM_Core_BAO_PdfFormat::getUnits(),
      FALSE,
      array('onChange' => "selectMetric( this.value );")
    );
    $form->add(
      'text',
      'margin_left',
      ts('Left Margin'),
      array('size' => 8, 'maxlength' => 8, 'onkeyup' => "showUpdateFormatChkBox();"),
      TRUE
    );
    $form->add(
      'text',
      'margin_right',
      ts('Right Margin'),
      array('size' => 8, 'maxlength' => 8, 'onkeyup' => "showUpdateFormatChkBox();"),
      TRUE
    );
    $form->add(
      'text',
      'margin_top',
      ts('Top Margin'),
      array('size' => 8, 'maxlength' => 8, 'onkeyup' => "showUpdateFormatChkBox();"),
      TRUE
    );
    $form->add(
      'text',
      'margin_bottom',
      ts('Bottom Margin'),
      array('size' => 8, 'maxlength' => 8, 'onkeyup' => "showUpdateFormatChkBox();"),
      TRUE
    );

    $config = CRM_Core_Config::singleton();
    /** CRM-15883 Suppressing Stationery path field until we switch from DOMPDF to a library that supports it.
    if ($config->wkhtmltopdfPath == FALSE) {
    $form->add(
    'text',
    'stationery',
    ts('Stationery (relative path to PDF you wish to use as the background)'),
    array('size' => 25, 'maxlength' => 900, 'onkeyup' => "showUpdateFormatChkBox();"),
    FALSE
    );
    }
     */
    $form->add('checkbox', 'bind_format', ts('Always use this Page Format with the selected Template'));
    $form->add('checkbox', 'update_format', ts('Update Page Format (this will affect all templates that use this format)'));

    $form->assign('useThisPageFormat', ts('Always use this Page Format with the new template?'));
    $form->assign('useSelectedPageFormat', ts('Should the new template always use the selected Page Format?'));
    $form->assign('totalSelectedContacts', count($form->_contactIds));

    $form->add('select', 'document_type', ts('Document Type'), CRM_Core_SelectValues::documentFormat());

    $documentTypes = implode(',', CRM_Core_SelectValues::documentApplicationType());
    $form->addElement('file', "document_file", 'Upload Document', 'size=30 maxlength=255 accept="' . $documentTypes . '"');
    $form->addUploadElement("document_file");

    CRM_Mailing_BAO_Mailing::commonCompose($form);

    $buttons = array();
    if ($form->get('action') != CRM_Core_Action::VIEW) {
      $buttons[] = array(
        'type' => 'upload',
        'name' => ts('Download Document'),
        'isDefault' => TRUE,
        'icon' => 'fa-download',
      );
      $buttons[] = array(
        'type' => 'submit',
        'name' => ts('Preview'),
        'subName' => 'preview',
        'icon' => 'fa-search',
        'isDefault' => FALSE,
      );
    }
    $buttons[] = array(
      'type' => 'cancel',
      'name' => $form->get('action') == CRM_Core_Action::VIEW ? ts('Done') : ts('Cancel'),
    );
    $form->addButtons($buttons);

    $form->addFormRule(array('CRM_Contact_Form_Task_PDFLetterCommon', 'formRule'), $form);
  }

  /**
   * Set default values.
   */
  public static function setDefaultValues() {
    $defaultFormat = CRM_Core_BAO_PdfFormat::getDefaultValues();
    $defaultFormat['format_id'] = $defaultFormat['id'];
    return $defaultFormat;
  }

  /**
   * Form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   * @param array $self
   *   Additional values form 'this'.
   *
   * @return bool
   *   TRUE if no errors, else array of errors.
   */
  public static function formRule($fields, $files, $self) {
    $errors = array();
    $template = CRM_Core_Smarty::singleton();

    // If user uploads non-document file other than odt/docx
    if (empty($fields['template']) &&
      !empty($files['document_file']['tmp_name']) &&
      array_search($files['document_file']['type'], CRM_Core_SelectValues::documentApplicationType()) == NULL
    ) {
      $errors['document_file'] = ts('Invalid document file format');
    }
    //Added for CRM-1393
    if (!empty($fields['saveTemplate']) && empty($fields['saveTemplateName'])) {
      $errors['saveTemplateName'] = ts("Enter name to save message template");
    }
    if (!is_numeric($fields['margin_left'])) {
      $errors['margin_left'] = 'Margin must be numeric';
    }
    if (!is_numeric($fields['margin_right'])) {
      $errors['margin_right'] = 'Margin must be numeric';
    }
    if (!is_numeric($fields['margin_top'])) {
      $errors['margin_top'] = 'Margin must be numeric';
    }
    if (!is_numeric($fields['margin_bottom'])) {
      $errors['margin_bottom'] = 'Margin must be numeric';
    }
    return empty($errors) ? TRUE : $errors;
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
  public static function processMessageTemplate($formValues) {
    $html_message = CRM_Utils_Array::value('html_message', $formValues);

    // process message template
    if (!empty($formValues['saveTemplate']) || !empty($formValues['updateTemplate'])) {
      $messageTemplate = array(
        'msg_text' => NULL,
        'msg_html' => $formValues['html_message'],
        'msg_subject' => NULL,
        'is_active' => TRUE,
      );

      $messageTemplate['pdf_format_id'] = 'null';
      if (!empty($formValues['bind_format']) && $formValues['format_id']) {
        $messageTemplate['pdf_format_id'] = $formValues['format_id'];
      }
      if (!empty($formValues['saveTemplate']) && $formValues['saveTemplate']) {
        $messageTemplate['msg_title'] = $formValues['saveTemplateName'];
        CRM_Core_BAO_MessageTemplate::add($messageTemplate);
      }

      if (!empty($formValues['updateTemplate']) && $formValues['template'] && $formValues['updateTemplate']) {
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
      foreach ((array) $documentInfo as $info) {
        list($html_message, $formValues['document_type']) = CRM_Utils_PDF_Document::docReader($info['fullPath'], $info['mime_type']);
        $formValues['document_file_path'] = $info['fullPath'];
      }
    }
    // extract the content of uploaded document file
    elseif (!empty($formValues['document_file'])) {
      list($html_message, $formValues['document_type']) = CRM_Utils_PDF_Document::docReader($formValues['document_file']['name'], $formValues['document_file']['type']);
      $formValues['document_file_path'] = $formValues['document_file']['name'];
    }

    if (!empty($formValues['update_format'])) {
      $bao = new CRM_Core_BAO_PdfFormat();
      $bao->savePdfFormat($formValues, $formValues['format_id']);
    }

    $categories = self::getTokenCategories();

    //time being hack to strip '&nbsp;'
    //from particular letter line, CRM-6798
    self::formatMessage($html_message);

    $messageToken = CRM_Utils_Token::getTokens($html_message);

    $returnProperties = array();
    if (isset($messageToken['contact'])) {
      foreach ($messageToken['contact'] as $key => $value) {
        $returnProperties[$value] = 1;
      }
    }

    return array($formValues, $categories, $html_message, $messageToken, $returnProperties);
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @param CRM_Core_Form $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function postProcess(&$form) {
    $formValues = $form->controller->exportValues($form->getName());
    list($formValues, $categories, $html_message, $messageToken, $returnProperties) = self::processMessageTemplate($formValues);
    $buttonName = $form->controller->getButtonName();
    $skipOnHold = isset($form->skipOnHold) ? $form->skipOnHold : FALSE;
    $skipDeceased = isset($form->skipDeceased) ? $form->skipDeceased : TRUE;
    $html = $activityIds = array();

    // CRM-21255 - Hrm, CiviCase 4+5 seem to report buttons differently...
    $c = $form->controller->container();
    $isLiveMode = ($buttonName == '_qf_PDF_upload') || isset($c['values']['PDF']['buttons']['_qf_PDF_upload']);

    // CRM-16725 Skip creation of activities if user is previewing their PDF letter(s)
    if ($isLiveMode) {
      $activityIds = self::createActivities($form, $html_message, $form->_contactIds, $formValues['subject'], CRM_Utils_Array::value('campaign_id', $formValues));
    }

    if (!empty($formValues['document_file_path'])) {
      list($html_message, $zip) = CRM_Utils_PDF_Document::unzipDoc($formValues['document_file_path'], $formValues['document_type']);
    }

    foreach ($form->_contactIds as $item => $contactId) {
      $caseId = NULL;
      $params = array('contact_id' => $contactId);

      list($contact) = CRM_Utils_Token::getTokenDetails($params,
        $returnProperties,
        $skipOnHold,
        $skipDeceased,
        NULL,
        $messageToken,
        'CRM_Contact_Form_Task_PDFLetterCommon'
      );

      if (civicrm_error($contact)) {
        $notSent[] = $contactId;
        continue;
      }

      $tokenHtml = CRM_Utils_Token::replaceContactTokens($html_message, $contact[$contactId], TRUE, $messageToken);
      if (!empty($form->_caseId)) {
        $caseId = $form->_caseId;
      }
      if (empty($caseId) && !empty($form->_caseIds[$item])) {
        $caseId = $form->_caseIds[$item];
      }
      if ($caseId) {
        $tokenHtml = CRM_Utils_Token::replaceCaseTokens($caseId, $tokenHtml, $messageToken);
      }
      $tokenHtml = CRM_Utils_Token::replaceHookTokens($tokenHtml, $contact[$contactId], $categories, TRUE);

      if (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY) {
        $smarty = CRM_Core_Smarty::singleton();
        // also add the contact tokens to the template
        $smarty->assign_by_ref('contact', $contact);
        $tokenHtml = $smarty->fetch("string:$tokenHtml");
      }

      $html[] = $tokenHtml;
    }

    $tee = NULL;
    if ($isLiveMode && Civi::settings()->get('recordGeneratedLetters') === 'combined-attached') {
      if (count($activityIds) !== 1) {
        throw new CRM_Core_Exception("When recordGeneratedLetters=combined-attached, there should only be one activity.");
      }
      $tee = CRM_Utils_ConsoleTee::create()->start();
    }

    $type = $formValues['document_type'];
    $mimeType = self::getMimeType($type);
    // ^^ Useful side-effect: consistently throws error for unrecognized types.

    if ($type == 'pdf') {
      $fileName = "CiviLetter.$type";
      CRM_Utils_PDF_Utils::html2pdf($html, $fileName, FALSE, $formValues);
    }
    elseif (!empty($formValues['document_file_path'])) {
      $fileName = pathinfo($formValues['document_file_path'], PATHINFO_FILENAME) . '.' . $type;
      CRM_Utils_PDF_Document::printDocuments($html, $fileName, $type, $zip);
    }
    else {
      $fileName = "CiviLetter.$type";
      CRM_Utils_PDF_Document::html2doc($html, $fileName, $formValues);
    }

    if ($tee) {
      $tee->stop();
      $content = file_get_contents($tee->getFileName(), NULL, NULL, NULL, 5);
      if (empty($content)) {
        throw new \CRM_Core_Exception("Failed to capture document content (type=$type)!");
      }
      foreach ($activityIds as $activityId) {
        civicrm_api3('Attachment', 'create', array(
          'entity_table' => 'civicrm_activity',
          'entity_id' => $activityId,
          'name' => $fileName,
          'mime_type' => $mimeType,
          'options' => array(
            'move-file' => $tee->getFileName(),
          ),
        ));
      }
    }

    $form->postProcessHook();

    CRM_Utils_System::civiExit(1);
  }

  /**
   * @param CRM_Core_Form $form
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
   * @throws CRM_Core_Exception
   */
  public static function createActivities($form, $html_message, $contactIds, $subject, $campaign_id, $perContactHtml = array()) {

    $activityParams = array(
      'subject' => $subject,
      'campaign_id' => $campaign_id,
      'source_contact_id' => CRM_Core_Session::singleton()->getLoggedInContactID(),
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Print PDF Letter'),
      'activity_date_time' => date('YmdHis'),
      'details' => $html_message,
    );
    if (!empty($form->_activityId)) {
      $activityParams += array('id' => $form->_activityId);
    }

    $activityIds = array();
    switch (Civi::settings()->get('recordGeneratedLetters')) {
      case 'none':
        return array();

      case 'multiple':
        // One activity per contact.
        foreach ($contactIds as $i => $contactId) {
          $fullParams = array(
            'target_contact_id' => $contactId,
          ) + $activityParams;
          if (!empty($form->_caseId)) {
            $fullParams['case_id'] = $form->_caseId;
          }
          elseif (!empty($form->_caseIds[$i])) {
            $fullParams['case_id'] = $form->_caseIds[$i];
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
        $fullParams = array(
          'target_contact_id' => $contactIds,
        ) + $activityParams;
        if (!empty($form->_caseId)) {
          $fullParams['case_id'] = $form->_caseId;
        }
        elseif (!empty($form->_caseIds)) {
          $fullParams['case_id'] = $form->_caseIds;
        }
        $activity = civicrm_api3('Activity', 'create', $fullParams);
        $activityIds[] = $activity['id'];
        break;

      default:
        throw new CRM_Core_Exception("Unrecognized option in recordGeneratedLetters: " . Civi::settings()->get('recordGeneratedLetters'));
    }

    return $activityIds;
  }

  /**
   * @param $message
   */
  public static function formatMessage(&$message) {
    $newLineOperators = array(
      'p' => array(
        'oper' => '<p>',
        'pattern' => '/<(\s+)?p(\s+)?>/m',
      ),
      'br' => array(
        'oper' => '<br />',
        'pattern' => '/<(\s+)?br(\s+)?\/>/m',
      ),
    );

    $htmlMsg = preg_split($newLineOperators['p']['pattern'], $message);
    foreach ($htmlMsg as $k => & $m) {
      $messages = preg_split($newLineOperators['br']['pattern'], $m);
      foreach ($messages as $key => & $msg) {
        $msg = trim($msg);
        $matches = array();
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
   * Convert from a vague-type/file-extension to mime-type.
   *
   * @param string $type
   * @return string
   * @throws \CRM_Core_Exception
   */
  private static function getMimeType($type) {
    $mimeTypes = array(
      'pdf' => 'application/pdf',
      'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'odt' => 'application/vnd.oasis.opendocument.text',
      'html' => 'text/html',
    );
    if (isset($mimeTypes[$type])) {
      return $mimeTypes[$type];
    }
    else {
      throw new \CRM_Core_Exception("Cannot determine mime type");
    }
  }

  /**
   * Get the categories required for rendering tokens.
   *
   * @return array
   */
  protected static function getTokenCategories() {
    if (!isset(Civi::$statics[__CLASS__]['token_categories'])) {
      $tokens = array();
      CRM_Utils_Hook::tokens($tokens);
      Civi::$statics[__CLASS__]['token_categories'] = array_keys($tokens);
    }
    return Civi::$statics[__CLASS__]['token_categories'];
  }

}
