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
 * This is the base class for common PDF/Doc Merge functionality.
 * Most CRM_*_Form_Task_PDFLetterCommon classes extend the Contact version
 * but the assumptions there are not always appropriate for other classes
 * resulting in code duplication and unexpected dependencies.
 * The intention is that common functionality can be moved here and the other
 * classes cleaned up.
 * Keep old-style token handling out of this class.
 *
 * @deprecated
 */
class CRM_Core_Form_Task_PDFLetterCommon {

  /**
   * @var CRM_Core_Form $form
   *
   * @deprecated
   */
  public static function preProcess(&$form) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');

    $form->setTitle(ts('Print/Merge Document'));
  }

  /**
   * Build the form object.
   *
   * @deprecated
   *
   * @var CRM_Core_Form $form
   * @throws \CRM_Core_Exception
   */
  public static function buildQuickForm(&$form) {
    CRM_Core_Error::deprecatedFunctionWarning('no supported alternative for non-core code');
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

    // Added for dev/core#2121,
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
      ['onChange' => "selectPaper( this.value ); showUpdateFormatChkBox();"]
    );
    $form->add(
      'select',
      'orientation',
      ts('Orientation'),
      CRM_Core_BAO_PdfFormat::getPageOrientations(),
      FALSE,
      ['onChange' => "updatePaperDimensions(); showUpdateFormatChkBox();"]
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

    $buttons = [];
    if ($form->get('action') != CRM_Core_Action::VIEW) {
      $buttons[] = [
        'type' => 'upload',
        'name' => ts('Download Document'),
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
      'name' => $form->get('action') == CRM_Core_Action::VIEW ? ts('Done') : ts('Cancel'),
    ];
    $form->addButtons($buttons);

    $form->addFormRule(['CRM_Core_Form_Task_PDFLetterCommon', 'formRule'], $form);
  }

  /**
   * Set default values.
   *
   * @deprecated
   */
  public static function setDefaultValues() {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
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
   * @param self $self
   *   Additional values form 'this'.
   *
   * @return bool
   *   TRUE if no errors, else array of errors.
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    $deprecatedTokens = [
      '{case.status_id}' => '{case.status_id:label}',
      '{case.case_type_id}' => '{case.case_type_id:label}',
      '{membership.status}' => '{membership.status_id:label}',
      '{membership.type}' => '{membership.membership_type_id:label}',
      '{contribution.campaign}' => '{contribution.campaign_id:label}',
      '{contribution.payment_instrument}' => '{contribution.payment_instrument_id:label}',
      '{contribution.contribution_id}' => '{contribution.id}',
      '{contribution.contribution_source}' => '{contribution.source}',
    ];
    $tokenErrors = [];
    foreach ($deprecatedTokens as $token => $replacement) {
      if (strpos($fields['html_message'], $token) !== FALSE) {
        $tokenErrors[] = ts('Token %1 is no longer supported - use %2 instead', [$token, $replacement]);
      }
    }
    if (!empty($tokenErrors)) {
      $errors['html_message'] = implode('<br>', $tokenErrors);
    }

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
      $errors['margin_left'] = ts('Margin must be numeric');
    }
    if (!is_numeric($fields['margin_right'])) {
      $errors['margin_right'] = ts('Margin must be numeric');
    }
    if (!is_numeric($fields['margin_top'])) {
      $errors['margin_top'] = ts('Margin must be numeric');
    }
    if (!is_numeric($fields['margin_bottom'])) {
      $errors['margin_bottom'] = ts('Margin must be numeric');
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Handle the template processing part of the form
   *
   * @param array $formValues
   *
   * @return string $html_message
   *
   * @deprecated
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function processTemplate(&$formValues) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');

    $html_message = $formValues['html_message'] ?? NULL;

    // process message template
    if (!empty($formValues['saveTemplate']) || !empty($formValues['updateTemplate'])) {
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
      if (!empty($formValues['saveTemplate'])) {
        $messageTemplate['msg_title'] = $formValues['saveTemplateName'];
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
      foreach ((array) $documentInfo as $info) {
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
   * @deprecated
   *
   * @param string $message
   */
  public static function formatMessage(&$message) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
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
   * Render html from rows
   *
   * @param $rows
   * @param string $msgPart
   *   The name registered with the TokenProcessor
   * @param array $formValues
   *   The values submitted through the form
   *
   * @deprecated
   */
  public static function renderFromRows($rows, $msgPart, $formValues): void {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    $html = [];
    foreach ($rows as $row) {
      $html[] = $row->render($msgPart);
    }

    if (!empty($html)) {
      self::outputFromHtml($formValues, $html);
    }
  }

  /**
   * List the available tokens
   * @return array of token name => label
   *
   * @deprecated
   */
  public static function listTokens() {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    $class = get_called_class();
    if (method_exists($class, 'createTokenProcessor')) {
      return $class::createTokenProcessor()->listTokens();
    }
  }

  /**
   * Output the pdf or word document from the generated html.
   *
   * @deprecated
   *
   * @param array $formValues
   * @param array $html
   */
  protected static function outputFromHtml($formValues, array $html) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    // Set the filename for the PDF using the Activity Subject, if defined. Remove unwanted characters and limit the length to 200 characters.
    if (!empty($formValues['subject'])) {
      $fileName = CRM_Utils_File::makeFilenameWithUnicode($formValues['subject'], '_', 200);
    }
    else {
      $fileName = 'CiviLetter';
    }
    if ($formValues['document_type'] === 'pdf') {
      CRM_Utils_PDF_Utils::html2pdf($html, $fileName . '.pdf', FALSE, $formValues);
    }
    else {
      CRM_Utils_PDF_Document::html2doc($html, $fileName . '.' . $formValues['document_type'], $formValues);
    }
  }

}
