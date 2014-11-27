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
 * This class contains all the function that are called using AJAX
 */
class CRM_Mailing_Page_AJAX {

  /**
   * Function to fetch the template text/html messages
   */
  public static function template() {
    $templateId = CRM_Utils_Type::escape($_POST['tid'], 'Integer');

    $messageTemplate = new CRM_Core_DAO_MessageTemplate();
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

    CRM_Utils_JSON::output($messages);
  }

  /**
   * Function to retrieve contact mailings
   */
  public static function getContactMailings() {
    $contactID = CRM_Utils_Type::escape($_GET['contact_id'], 'Integer');

    $sortMapper = array(
      0 => 'subject', 1 => 'creator_name', 2 => '', 3 => 'start_date', 4 => '', 5 => 'links',
    );

    $sEcho     = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $offset    = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount  = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;
    $sort      = isset($_REQUEST['iSortCol_0']) ? CRM_Utils_Array::value(CRM_Utils_Type::escape($_REQUEST['iSortCol_0'], 'Integer'), $sortMapper) : NULL;
    $sortOrder = isset($_REQUEST['sSortDir_0']) ? CRM_Utils_Type::escape($_REQUEST['sSortDir_0'], 'String') : 'asc';

    $params = $_POST;
    if ($sort && $sortOrder) {
      $params['sortBy'] = $sort . ' ' . $sortOrder;
    }

    $params['page'] = ($offset / $rowCount) + 1;
    $params['rp'] = $rowCount;

    $params['contact_id'] = $contactID;
    $params['context'] = $context;

    // get the contact mailings
    $mailings = CRM_Mailing_BAO_Mailing::getContactMailingSelector($params);

    $iFilteredTotal = $iTotal = $params['total'];
    $selectorElements = array(
      'subject', 'mailing_creator', 'recipients',
      'start_date', 'openstats', 'links',
    );

    echo CRM_Utils_JSON::encodeDataTableSelector($mailings, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }
}

