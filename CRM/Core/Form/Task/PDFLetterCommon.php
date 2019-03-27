<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This is the base class for common PDF/Doc Merge functionality.
 * Most CRM_*_Form_Task_PDFLetterCommon classes extend the Contact version
 * but the assumptions there are not always appropriate for other classes
 * resulting in code duplication and unexpected dependencies.
 * The intention is that common functionality can be moved here and the other
 * classes cleaned up.
 * Keep old-style token handling out of this class.
 */
class CRM_Core_Form_Task_PDFLetterCommon {

  /**
   * @var CRM_Core_Form $form
   */
  public static function preProcess(&$form) {
    CRM_Utils_System::setTitle('Print/Merge Document');
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

    $form->addFormRule(array('CRM_Core_Form_Task_PDFLetterCommon', 'formRule'), $form);
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
   * Handle the template processing part of the form
   */
  public static function processTemplate(&$formValues) {
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

    return $html_message;
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

}
