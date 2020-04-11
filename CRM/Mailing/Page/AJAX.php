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
 * This class contains all the function that are called using AJAX
 */
class CRM_Mailing_Page_AJAX {

  /**
   * Fetch the template text/html messages
   */
  public static function template() {
    $templateId = CRM_Utils_Type::escape($_POST['tid'], 'Integer');

    $messageTemplate = new CRM_Core_DAO_MessageTemplate();
    $messageTemplate->id = $templateId;
    $messageTemplate->selectAdd();
    $messageTemplate->selectAdd('msg_text, msg_html, msg_subject, pdf_format_id');
    $messageTemplate->find(TRUE);
    $messages = [
      'subject' => $messageTemplate->msg_subject,
      'msg_text' => $messageTemplate->msg_text,
      'msg_html' => $messageTemplate->msg_html,
      'pdf_format_id' => $messageTemplate->pdf_format_id,
    ];

    $documentInfo = CRM_Core_BAO_File::getEntityFile('civicrm_msg_template', $templateId);
    foreach ((array) $documentInfo as $info) {
      list($messages['document_body']) = CRM_Utils_PDF_Document::docReader($info['fullPath'], $info['mime_type']);
    }

    CRM_Utils_JSON::output($messages);
  }

  /**
   * Retrieve contact mailings.
   */
  public static function getContactMailings() {
    $params = CRM_Core_Page_AJAX::defaultSortAndPagerParams();
    $params += CRM_Core_Page_AJAX::validateParams(['contact_id' => 'Integer']);

    // get the contact mailings
    $mailings = CRM_Mailing_BAO_Mailing::getContactMailingSelector($params);

    CRM_Utils_JSON::output($mailings);
  }

}
