<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * a page for mailing preview
 */
class CRM_Mailing_Page_Preview extends CRM_Core_Page {

  /**
   * run this page (figure out the action needed and perform it).
   *
   * @return void
   */
  function run() {

    $session = CRM_Core_Session::singleton();

    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', CRM_Core_DAO::$_nullObject, FALSE, 'text');
    $type = CRM_Utils_Request::retrieve('type', 'String', CRM_Core_DAO::$_nullObject, FALSE, 'text');

    $options = array();
    $session->getVars($options, "CRM_Mailing_Controller_Send_$qfKey");

    //get the options if control come from search context, CRM-3711
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

    //get details of contact with token value including Custom Field Token Values.CRM-3734
    $returnProperties = $mailing->getReturnProperties();
    $params = array('contact_id' => $session->get('userID'));

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
      header('Content-Type: text/html; charset=utf-8');
      print $mime->getHTMLBody();
    }
    else {
      header('Content-Type: text/plain; charset=utf-8');
      print $mime->getTXTBody();
    }
    CRM_Utils_System::civiExit();
  }
}

