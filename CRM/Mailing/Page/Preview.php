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
 * a page for mailing preview
 */
class CRM_Mailing_Page_Preview extends CRM_Core_Page {

  /**
   * Run this page (figure out the action needed and perform it).
   */
  public function run() {

    $session = CRM_Core_Session::singleton();

    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', CRM_Core_DAO::$_nullObject, FALSE, 'text');
    $type = CRM_Utils_Request::retrieve('type', 'String', CRM_Core_DAO::$_nullObject, FALSE, 'text');

    $options = [];
    $session->getVars($options, "CRM_Mailing_Controller_Send_$qfKey");

    // get the options if control come from search context, CRM-3711
    if (empty($options)) {
      $session->getVars($options, "CRM_Contact_Controller_Search_$qfKey");
    }

    // FIXME: the below and CRM_Mailing_Form_Test::testMail()
    // should be refactored
    $fromEmail = NULL;
    $mailing = new CRM_Mailing_BAO_Mailing();
    if (!empty($options)) {
      $mailing->id = $options['mailing_id'];
      $fromEmail = CRM_Utils_Array::value('from_email', $options);
    }

    $mailing->find(TRUE);

    CRM_Mailing_BAO_Mailing::tokenReplace($mailing);

    // get and format attachments
    $attachments = CRM_Core_BAO_File::getEntityFile('civicrm_mailing',
      $mailing->id
    );

    // get details of contact with token value including Custom Field Token Values.CRM-3734
    $returnProperties = $mailing->getReturnProperties();
    $params = ['contact_id' => $session->get('userID')];

    $details = CRM_Utils_Token::getTokenDetails($params,
      $returnProperties,
      TRUE, TRUE, NULL,
      $mailing->getFlattenedTokens(),
      get_class($this)
    );

    $mime = &$mailing->compose(NULL, NULL, NULL, $session->get('userID'), $fromEmail, $fromEmail,
      TRUE, $details[0][$session->get('userID')], $attachments
    );

    if ($type == 'html') {
      CRM_Utils_System::setHttpHeader('Content-Type', 'text/html; charset=utf-8');
      print $mime->getHTMLBody();
    }
    else {
      CRM_Utils_System::setHttpHeader('Content-Type', 'text/plain; charset=utf-8');
      print $mime->getTXTBody();
    }
    CRM_Utils_System::civiExit();
  }

}
