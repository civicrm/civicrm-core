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
 * Form to configure thank-you messages and receipting features for an online contribution page.
 */
class CRM_Contribute_Form_ContributionPage_ThankYou extends CRM_Contribute_Form_ContributionPage {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();
    $this->setSelectedChild('thankyou');
  }

  /**
   * Set default values for the form.
   *
   * Note that in edit/view mode the default values are retrieved from the database.
   */
  public function setDefaultValues() {
    return parent::setDefaultValues();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->registerRule('emailList', 'callback', 'emailList', 'CRM_Utils_Rule');

    // thank you title and text (html allowed in text)
    $this->add('text', 'thankyou_title', ts('Thank-you Page Title'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'thankyou_title'), TRUE);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'thankyou_text') + ['class' => 'collapsed'];
    $this->add('wysiwyg', 'thankyou_text', ts('Thank-you Message'), $attributes);
    $this->add('wysiwyg', 'thankyou_footer', ts('Thank-you Footer'), $attributes);

    $this->addElement('checkbox', 'is_email_receipt', ts('Email Receipt to Contributor?'), NULL, ['onclick' => "showReceipt()"]);
    $this->add('text', 'receipt_from_name', ts('Receipt From Name'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'receipt_from_name'));
    $this->add('text', 'receipt_from_email', ts('Receipt From Email'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'receipt_from_email'));
    $this->add('textarea', 'receipt_text', ts('Receipt Message'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'receipt_text'));

    $this->add('text', 'cc_receipt', ts('CC Receipt To'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'cc_receipt'));
    $this->addRule('cc_receipt', ts('Please enter a valid list of comma delimited email addresses'), 'emailList');

    $this->add('text', 'bcc_receipt', ts('BCC Receipt To'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'bcc_receipt'));
    $this->addRule('bcc_receipt', ts('Please enter a valid list of comma delimited email addresses'), 'emailList');

    parent::buildQuickForm();
    $this->addFormRule(['CRM_Contribute_Form_ContributionPage_ThankYou', 'formRule'], $this);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param array $options
   *   Additional user data.
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $options) {
    $errors = [];

    // if is_email_receipt is set, the receipt message must be non-empty
    if (!empty($fields['is_email_receipt'])) {
      //added for CRM-1348
      $email = trim($fields['receipt_from_email'] ?? '');
      if (empty($email) || !CRM_Utils_Rule::email($email)) {
        $errors['receipt_from_email'] = ts('A valid Receipt From Email address must be specified if Email Receipt to Contributor is enabled');
      }
    }
    return $errors;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    $params['id'] = $this->_id;
    $params['is_email_receipt'] ??= FALSE;
    if (!$params['is_email_receipt']) {
      $params['receipt_from_name'] = NULL;
      $params['receipt_from_email'] = NULL;
      $params['receipt_text'] = NULL;
      $params['cc_receipt'] = NULL;
      $params['bcc_receipt'] = NULL;
    }

    CRM_Contribute_BAO_ContributionPage::writeRecord($params);
    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Thanks and Receipt');
  }

}
