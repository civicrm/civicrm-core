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
 * A page for mailing preview.
 */
class CRM_Mailing_Page_View extends CRM_Core_Page {
  protected $_mailingID;
  protected $_mailing;
  protected $_contactID;

  /**
   * Lets do permission checking here.
   * First check for valid mailing, if false return fatal.
   * Second check for visibility.
   * Call a hook to see if hook wants to override visibility setting.
   */
  public function checkPermission() {
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
      CRM_Core_Permission::check('approve mailings') ||
      CRM_Core_Permission::check('access CiviMail')
    ) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Run this page (figure out the action needed and perform it).
   *
   * @param int $id
   * @param int $contactID
   * @param bool $print
   * @param bool $allowID
   *
   * @return null|string
   *   Not really sure if anything should be returned - parent doesn't
   */
  public function run($id = NULL, $contactID = NULL, $print = TRUE, $allowID = FALSE) {
    if (is_numeric($id)) {
      $this->_mailingID = $id;
    }
    else {
      $print = TRUE;
      $this->_mailingID = CRM_Utils_Request::retrieve('id', 'String', CRM_Core_DAO::$_nullObject, TRUE);
    }

    // # CRM-7651
    // override contactID from the function level if passed in
    if (isset($contactID) &&
      is_numeric($contactID)
    ) {
      $this->_contactID = $contactID;
    }
    else {
      $this->_contactID = CRM_Core_Session::getLoggedInContactID();
    }

    // mailing key check
    if (Civi::settings()->get('hash_mailing_url')) {
      $this->_mailing = new CRM_Mailing_BAO_Mailing();

      if (!is_numeric($this->_mailingID)) {
        $this->_mailing->hash = $this->_mailingID;
      }
      elseif (is_numeric($this->_mailingID)) {
        $this->_mailing->id = $this->_mailingID;
        // if mailing is present and associated hash is present
        // while 'hash' is not been used for mailing view : throw 'permissionDenied'
        if ($this->_mailing->find() &&
          CRM_Core_DAO::getFieldValue('CRM_Mailing_BAO_Mailing', $this->_mailingID, 'hash', 'id') &&
          !$allowID
        ) {
          CRM_Utils_System::permissionDenied();
          return NULL;
        }
      }
    }
    else {
      $this->_mailing = new CRM_Mailing_BAO_Mailing();
      $this->_mailing->id = $this->_mailingID;
    }

    if (!$this->_mailing->find(TRUE) ||
      !$this->checkPermission()
    ) {
      CRM_Utils_System::permissionDenied();
      return NULL;
    }

    CRM_Mailing_BAO_Mailing::tokenReplace($this->_mailing);

    // get and format attachments
    $attachments = CRM_Core_BAO_File::getEntityFile('civicrm_mailing',
      $this->_mailing->id
    );

    // get contact detail and compose if contact id exists
    $returnProperties = $this->_mailing->getReturnProperties();
    if (isset($this->_contactID)) {
      // get details of contact with token value including Custom Field Token Values.CRM-3734
      $params = ['contact_id' => $this->_contactID];
      $details = CRM_Utils_Token::getTokenDetails($params,
        $returnProperties,
        FALSE, TRUE, NULL,
        $this->_mailing->getFlattenedTokens(),
        get_class($this)
      );
      $details = $details[0][$this->_contactID];
      $contactId = $this->_contactID;
    }
    else {
      // get tokens that are not contact specific resolved
      $params = ['contact_id' => 0];
      $details = CRM_Utils_Token::getAnonymousTokenDetails($params,
        $returnProperties,
        TRUE, TRUE, NULL,
        $this->_mailing->getFlattenedTokens(),
        get_class($this)
      );

      $details = CRM_Utils_Array::value(0, $details[0]);
      $contactId = 0;
    }
    $mime = $this->_mailing->compose(NULL, NULL, NULL, $contactId,
      $this->_mailing->from_email,
      $this->_mailing->from_email,
      TRUE, $details, $attachments
    );

    $title = NULL;
    if (isset($this->_mailing->body_html) && empty($_GET['text'])) {
      $header = 'text/html; charset=utf-8';
      $content = $mime->getHTMLBody();
      if (strpos($content, '<head>') === FALSE && strpos($content, '<title>') === FALSE) {
        $title = '<head><title>' . $this->_mailing->subject . '</title></head>';
      }
    }
    else {
      $header = 'text/plain; charset=utf-8';
      $content = $mime->getTXTBody();
    }
    CRM_Utils_System::setTitle($this->_mailing->subject);

    if (CRM_Utils_Array::value('snippet', $_GET) === 'json') {
      CRM_Core_Page_AJAX::returnJsonResponse($content);
    }
    if ($print) {
      CRM_Utils_System::setHttpHeader('Content-Type', $header);
      print $title;
      print $content;
      CRM_Utils_System::civiExit();
    }
    else {
      return $content;
    }
  }

}
