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
 * This class provides the common functionality for creating PDF letter for one or a group of contact ids.
 */
class CRM_Contact_Form_Task_PDFLetterCommon extends CRM_Core_Form_Task_PDFLetterCommon {

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
    parent::preProcess($form);
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
   * Part of the post process which prepare and extract information from the template.
   *
   *
   * @param array $formValues
   *
   * @return array
   *   [$categories, $html_message, $messageToken, $returnProperties]
   */
  public static function processMessageTemplate($formValues) {
    $html_message = self::processTemplate($formValues);

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

    CRM_Utils_System::civiExit();
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
