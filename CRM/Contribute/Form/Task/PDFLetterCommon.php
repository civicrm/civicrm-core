<?php

/**
 * This class provides the common functionality for creating PDF letter for
 * one or a group of contact ids.
 */
class CRM_Contribute_Form_Task_PDFLetterCommon extends CRM_Contact_Form_Task_PDFLetterCommon {

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  static function postProcess(&$form) {

    list($formValues, $categories, $html_message, $messageToken, $returnProperties) = self::processMessageTemplate($form);

    // update dates ?
    $receipt_update  = isset($formValues['receipt_update']) ? $formValues['receipt_update'] : FALSE;
    $thankyou_update = isset($formValues['thankyou_update']) ? $formValues['thankyou_update'] : FALSE;
    $nowDate         = date('YmdHis');
    $receipts        = 0;
    $thanks          = 0;
    $updateStatus    = '';

    // skip some contacts ?
    $skipOnHold = isset($form->skipOnHold) ? $form->skipOnHold : FALSE;
    $skipDeceased = isset($form->skipDeceased) ? $form->skipDeceased : TRUE;

    foreach ($form->getVar('_contributionIds') as $item => $contributionId) {

      // get contact information
      $contactId = civicrm_api("Contribution", "getvalue", array('version' => '3', 'id' => $contributionId, 'return' => 'contact_id'));
      $params = array('contact_id' => $contactId);

      list($contact) = CRM_Utils_Token::getTokenDetails($params,
        $returnProperties,
        $skipOnHold,
        $skipDeceased,
        NULL,
        $messageToken,
        'CRM_Contribution_Form_Task_PDFLetterCommon'
      );
      if (civicrm_error($contact)) {
        $notSent[] = $contributionId;
        continue;
      }

      // get contribution information
      $params = array('contribution_id' => $contributionId);
      $contribution = CRM_Utils_Token::getContributionTokenDetails($params,
        $returnProperties,
        NULL,
        $messageToken,
        'CRM_Contribution_Form_Task_PDFLetterCommon'
      );
      if (civicrm_error($contribution)) {
        $notSent[] = $contributionId;
        continue;
      }

      $tokenHtml = CRM_Utils_Token::replaceContactTokens($html_message, $contact[$contactId], TRUE, $messageToken);
      $tokenHtml = CRM_Utils_Token::replaceContributionTokens($tokenHtml, $contribution[$contributionId], TRUE, $messageToken);
      $tokenHtml = CRM_Utils_Token::replaceHookTokens($tokenHtml, $contact[$contactId], $categories, TRUE);

      if (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY) {
        $smarty = CRM_Core_Smarty::singleton();
        // also add the tokens to the template
        $smarty->assign_by_ref('contact', $contact);
        $tokenHtml = $smarty->fetch("string:$tokenHtml");
      }

      $html[] = $tokenHtml;

      // update dates (do it for each contribution including grouped recurring contribution)
      if ($receipt_update) {
        $result = CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'receipt_date', $nowDate);
        // We can't use CRM_Core_Error::fatal here because the api error elevates the exception level. FIXME. dgg
        if ($result) {
          $receipts++;
      }
      }
      if ($thankyou_update) {
        $result = CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'thankyou_date', $nowDate);
        // We can't use CRM_Core_Error::fatal here because the api error elevates the exception level. FIXME. dgg
        if ($result) {
          $thanks++;
      }
    }
    }

    self::createActivities($form, $html_message, $form->_contactIds);

    CRM_Utils_PDF_Utils::html2pdf($html, "CiviLetter.pdf", FALSE, $formValues);

    $form->postProcessHook();

    if ($receipts) {
      $updateStatus = ts('Receipt date has been updated for %1 contributions.', array(1 => $receipts));
    }
    if ($thanks) {
      $updateStatus .= ' ' . ts('Thank-you date has been updated for %1 contributions.', array(1 => $thanks));
    }

    if ($updateStatus) {
      CRM_Core_Session::setStatus($updateStatus);
    }
    
    CRM_Utils_System::civiExit(1);
  }
  //end of function
}

