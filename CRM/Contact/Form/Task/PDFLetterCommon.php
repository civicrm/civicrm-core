<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class provides the common functionality for creating PDF letter for
 * one or a group of contact ids.
 */
class CRM_Contact_Form_Task_PDFLetterCommon {

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  static function preProcess(&$form) {
    $messageText    = array();
    $messageSubject = array();
    $dao            = new CRM_Core_BAO_MessageTemplates();
    $dao->is_active = 1;
    $dao->find();
    while ($dao->fetch()) {
      $messageText[$dao->id] = $dao->msg_text;
      $messageSubject[$dao->id] = $dao->msg_subject;
    }

    $form->assign('message', $messageText);
    $form->assign('messageSubject', $messageSubject);
  }

  static function preProcessSingle(&$form, $cid) {
    $form->_contactIds = array($cid);
    // put contact display name in title for single contact mode
    CRM_Contact_Page_View::setTitle($cid);
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  static function buildQuickForm(&$form) {
    $form->add('static', 'pdf_format_header', NULL, ts('Page Format'));
    $form->add(
      'select',
      'format_id',
      ts('Select Format'),
      array(0 => ts('- default -')) + CRM_Core_BAO_PdfFormat::getList(TRUE),
      FALSE,
      array('onChange' => "selectFormat( this.value, false );")
    );;
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
    $form->add('checkbox', 'bind_format', ts('Always use this Page Format with the selected Template'));
    $form->add('checkbox', 'update_format', ts('Update Page Format (this will affect all templates that use this format)'));

    $form->assign('useThisPageFormat', ts('Always use this Page Format with the new template?'));
    $form->assign('useSelectedPageFormat', ts('Should the new template always use the selected Page Format?'));
    $form->assign('totalSelectedContacts', count($form->_contactIds));

    CRM_Mailing_BAO_Mailing::commonLetterCompose($form);

    if ($form->_single) {
      $cancelURL = CRM_Utils_System::url(
        'civicrm/contact/view',
        "reset=1&cid={$form->_cid}&selectedChild=activity",
        FALSE,
        NULL,
        FALSE
      );
      if ($form->get('action') == CRM_Core_Action::VIEW) {
        $form->addButtons(array(
            array(
              'type' => 'cancel',
              'name' => ts('Done'),
              'js' => array('onclick' => "location.href='{$cancelURL}'; return false;"),
            ),
          )
        );
      }
      else {
        $form->addButtons(array(
            array(
              'type' => 'submit',
              'name' => ts('Make PDF Letter'),
              'isDefault' => TRUE,
            ),
            array(
              'type' => 'cancel',
              'name' => ts('Done'),
              'js' => array('onclick' => "location.href='{$cancelURL}'; return false;"),
            ),
          )
        );
      }
    }
    else {
      $form->addDefaultButtons(ts('Make PDF Letters'));
    }

    $form->addFormRule(array('CRM_Contact_Form_Task_PDFLetterCommon', 'formRule'), $form);
  }

  /**
   * Set default values
   */
  static function setDefaultValues() {
    $defaultFormat = CRM_Core_BAO_PdfFormat::getDefaultValues();
    $defaultFormat['format_id'] = $defaultFormat['id'];
    return $defaultFormat;
  }

  /**
   * form rule
   *
   * @param array $fields    the input form values
   * @param array $dontCare
   * @param array $self      additional values form 'this'
   *
   * @return true if no errors, else array of errors
   * @access public
   *
   */
  static function formRule($fields, $dontCare, $self) {
    $errors = array();
    $template = CRM_Core_Smarty::singleton();

    //Added for CRM-1393
    if (CRM_Utils_Array::value('saveTemplate', $fields) && empty($fields['saveTemplateName'])) {
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
   * part of the post process which prepare and extract information from the template
   *
   * @access protected
   *
   * @return array( $categories, $html_message, $messageToken, $returnProperties )
   */
  static protected function processMessageTemplate(&$form) {
    $formValues = $form->controller->exportValues($form->getName());

    // process message template
    if (CRM_Utils_Array::value('saveTemplate', $formValues) || CRM_Utils_Array::value('updateTemplate', $formValues)) {
      $messageTemplate = array(
        'msg_text' => NULL,
        'msg_html' => $formValues['html_message'],
        'msg_subject' => NULL,
        'is_active' => TRUE,
      );

      $messageTemplate['pdf_format_id'] = 'null';
      if (CRM_Utils_Array::value('bind_format', $formValues) && $formValues['format_id'] > 0) {
        $messageTemplate['pdf_format_id'] = $formValues['format_id'];
      }
      if (CRM_Utils_Array::value('saveTemplate', $formValues) && $formValues['saveTemplate']) {
        $messageTemplate['msg_title'] = $formValues['saveTemplateName'];
        CRM_Core_BAO_MessageTemplates::add($messageTemplate);
      }

      if (CRM_Utils_Array::value('updateTemplate', $formValues) && $formValues['template'] && $formValues['updateTemplate']) {
        $messageTemplate['id'] = $formValues['template'];

        unset($messageTemplate['msg_title']);
        CRM_Core_BAO_MessageTemplates::add($messageTemplate);
      }
    }
    elseif (CRM_Utils_Array::value('template', $formValues) > 0) {
      if (CRM_Utils_Array::value('bind_format', $formValues) && $formValues['format_id'] > 0) {
        $query = "UPDATE civicrm_msg_template SET pdf_format_id = {$formValues['format_id']} WHERE id = {$formValues['template']}";
      }
      else {
        $query = "UPDATE civicrm_msg_template SET pdf_format_id = NULL WHERE id = {$formValues['template']}";
      }
      CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    }
    if (CRM_Utils_Array::value('update_format', $formValues)) {
      $bao = new CRM_Core_BAO_PdfFormat();
      $bao->savePdfFormat($formValues, $formValues['format_id']);
    }

    $html = array();

    $tokens = array();
    CRM_Utils_Hook::tokens($tokens);
    $categories = array_keys($tokens);

    $html_message = $formValues['html_message'];

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
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  static function postProcess(&$form) {
    list($formValues, $categories, $html_message, $messageToken, $returnProperties) = self::processMessageTemplate($form);

    $skipOnHold = isset($form->skipOnHold) ? $form->skipOnHold : FALSE;
    $skipDeceased = isset($form->skipDeceased) ? $form->skipDeceased : TRUE;

    foreach ($form->_contactIds as $item => $contactId) {
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
      $tokenHtml = CRM_Utils_Token::replaceHookTokens($tokenHtml, $contact[$contactId], $categories, TRUE);

      if (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY) {
        $smarty = CRM_Core_Smarty::singleton();
        // also add the contact tokens to the template
        $smarty->assign_by_ref('contact', $contact);
        $tokenHtml = $smarty->fetch("string:$tokenHtml");
      }

      $html[] = $tokenHtml;
    }

    self::createActivities($form, $html_message, $form->_contactIds);

    CRM_Utils_PDF_Utils::html2pdf($html, "CiviLetter.pdf", FALSE, $formValues);

    $form->postProcessHook();

    CRM_Utils_System::civiExit(1);
  }

  function createActivities($form, $html_message, $contactIds) {

    $session        = CRM_Core_Session::singleton();
    $userID         = $session->get('userID');
    $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type',
      'Print PDF Letter',
      'name'
    );
    $activityParams = array(
      'source_contact_id' => $userID,
      'activity_type_id' => $activityTypeID,
      'activity_date_time' => date('YmdHis'),
      'details' => $html_message,
    );
    if (!empty($form->_activityId)) {
      $activityParams += array('id' => $form->_activityId);
    }
    if ($form->_cid) {
      $activity = CRM_Activity_BAO_Activity::create($activityParams);
    }
    else {
      // create  Print PDF activity for each selected contact. CRM-6886
      $activityIds = array();
      foreach ($contactIds as $contactId) {
        $activityID = CRM_Activity_BAO_Activity::create($activityParams);
        $activityIds[$contactId] = $activityID->id;
      }
    }

    foreach ($form->_contactIds as $contactId) {
      $activityTargetParams = array(
        'activity_id' => empty($activity->id) ? $activityIds[$contactId] : $activity->id,
        'target_contact_id' => $contactId,
      );
      CRM_Activity_BAO_Activity::createActivityTarget($activityTargetParams);
    }
  }

  function formatMessage(&$message) {
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
          $trimMsg  = ltrim($msg, '&nbsp; ');
          $charLen  = strlen($trimMsg);
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

