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
    $form->setTitle('Print/Merge Document');
  }

}
