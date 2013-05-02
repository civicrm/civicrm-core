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
 * a page for mailing preview
 */
class CRM_Mailing_Page_View extends CRM_Core_Page {
  protected $_mailingID;
  protected $_mailing;
  protected $_contactID;

  /**
   * Lets do permission checking here
   * First check for valid mailing, if false return fatal
   * Second check for visibility
   * Call a hook to see if hook wants to override visibility setting
   */
  function checkPermission() {
    if (!$this->_mailing) {
      return FALSE;
    }

    // check for visibility, if visibility is Public Pages and they have the permission
    // return true
    if ($this->_mailing->visibility == 'Public Pages' &&
      CRM_Core_Permission::check('view public CiviMail content')
    ) {
      return TRUE;
    }

    // if user is an admin, return true
    if (CRM_Core_Permission::check('administer CiviCRM') ||
      CRM_Core_Permission::check('access CiviMail')
    ) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * run this page (figure out the action needed and perform it).
   *
   * @return void
   */
  function run($id = NULL, $contact_id = NULL, $print = TRUE) {
    if (is_numeric($id)) {
      $this->_mailingID = $id;
    }
    else {
      $print = TRUE;
      $this->_mailingID = CRM_Utils_Request::retrieve('id', 'Integer', CRM_Core_DAO::$_nullObject, TRUE);
    }

    // # CRM-7651
    // override contactID from the function level if passed in
    if (isset($contactID) &&
      is_numeric($contactID)
    ) {
      $this->_contactID = $contactID;
    }
    else {
      $session = CRM_Core_Session::singleton();
      $this->_contactID = $session->get('userID');
    }

    $this->_mailing = new CRM_Mailing_BAO_Mailing();
    $this->_mailing->id = $this->_mailingID;

    if (!$this->_mailing->find(TRUE) ||
      !$this->checkPermission()
    ) {
      CRM_Utils_System::permissionDenied();
      return;
    }

    CRM_Mailing_BAO_Mailing::tokenReplace($this->_mailing);

    // get and format attachments
    $attachments = CRM_Core_BAO_File::getEntityFile('civicrm_mailing',
      $this->_mailing->id
    );

    // get contact detail and compose if contact id exists
    if (isset($this->_contactID)) {
      //get details of contact with token value including Custom Field Token Values.CRM-3734
      $returnProperties = $this->_mailing->getReturnProperties();
      $params           = array('contact_id' => $this->_contactID);
      $details          = CRM_Utils_Token::getTokenDetails($params,
        $returnProperties,
        TRUE, TRUE, NULL,
        $this->_mailing->getFlattenedTokens(),
        get_class($this)
      );
      $details = $details[0][$this->_contactID];
    }
    else {
      $details = array('test');
    }
    $mime = &$this->_mailing->compose(NULL, NULL, NULL, 0,
      $this->_mailing->from_email,
      $this->_mailing->from_email,
      TRUE, $details, $attachments
    );

    if (isset($this->_mailing->body_html)) {
      $header = 'Content-Type: text/html; charset=utf-8';
      $content = $mime->getHTMLBody();
    }
    else {
      $header = 'Content-Type: text/plain; charset=utf-8';
      $content = $mime->getTXTBody();
    }

    if ($print) {
      header($header);
      print $content;
      CRM_Utils_System::civiExit();
    }
    else {
      return $content;
    }
  }
}

