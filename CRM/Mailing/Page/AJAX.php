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
 * This class contains all the function that are called using AJAX
 */
class CRM_Mailing_Page_AJAX {

  /**
   * Function to fetch the template text/html messages
   */
  static function template() {
    $templateId = CRM_Utils_Type::escape($_POST['tid'], 'Integer');

    $messageTemplate = new CRM_Core_DAO_MessageTemplates();
    $messageTemplate->id = $templateId;
    $messageTemplate->selectAdd();
    $messageTemplate->selectAdd('msg_text, msg_html, msg_subject, pdf_format_id');
    $messageTemplate->find(TRUE);
    $messages = array(
      'subject' => $messageTemplate->msg_subject,
      'msg_text' => $messageTemplate->msg_text,
      'msg_html' => $messageTemplate->msg_html,
      'pdf_format_id' => $messageTemplate->pdf_format_id,
    );

    echo json_encode($messages);
    CRM_Utils_System::civiExit();
  }
}

