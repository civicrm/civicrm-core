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
 * A page for mailing preview.
 */
class CRM_Mailing_Page_View extends CRM_Core_Page {

  protected $_mailingID;
  protected $_mailing;
  protected $_contactID;
  private $_mailingIDIsHash;

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
    return CRM_Core_Permission::check([
      ['administer CiviCRM', /* OR */ 'approve mailings', /* OR */ 'access CiviMail'],
    ]);
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
    if (empty($id) || is_array($id)) {
      $print = TRUE;
    }
    $this->getMailingID($id);

    // Retrieve contact ID and checksum from the URL
    $cs = CRM_Utils_Request::retrieve('cs', 'String');
    $cid = CRM_Utils_Request::retrieve('cid', 'Int');

    // # CRM-7651
    // override contactID from the function level if passed in
    if (isset($contactID) &&
      is_numeric($contactID)
    ) {
      $this->_contactID = $contactID;
    }

    // Support checksummed view of the mailing to replace tokens
    elseif (!empty($cs) && !empty($cid) && CRM_Contact_BAO_Contact_Utils::validChecksum($cid, $cs)) {
      $this->_contactID = $cid;
    }

    else {
      $this->_contactID = CRM_Core_Session::getLoggedInContactID();
    }

    // mailing key check
    if (Civi::settings()->get('hash_mailing_url')) {
      $this->_mailing = new CRM_Mailing_BAO_Mailing();
      if ($this->_mailingIDIsHash) {

        //lets get the id from the hash
        $result_id = civicrm_api3('Mailing', 'get', [
          'return' => ['id'],
          'hash' => $this->_mailingID,
        ]);
        $this->_mailing->hash = $this->_mailingID;
        $this->_mailingID     = $result_id['id'];
      }
      else {
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

    $contactId = $this->_contactID ?? 0;

    $result = civicrm_api3('Mailing', 'preview', [
      'id' => $this->_mailingID,
      'contact_id' => $contactId,
    ]);
    $mailing = $result['values'] ?? NULL;

    $title = NULL;
    if (!empty($mailing['body_html']) && empty($_GET['text'])) {
      $header = 'text/html; charset=utf-8';
      $content = $mailing['body_html'];
      if (strpos($content, '<head>') === FALSE && strpos($content, '<title>') === FALSE) {
        $title = '<head><title>' . $mailing['subject'] . '</title></head>';
      }
    }
    else {
      $header = 'text/plain; charset=utf-8';
      $content = $mailing['body_text'];
    }
    CRM_Utils_System::setTitle($mailing['subject']);

    if (($_GET['snippet'] ?? NULL) === 'json') {
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

  public function getMailingID($id): void {
    if (!empty($id) && !is_array($id)) {
      $check = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_mailing WHERE CAST(id AS CHAR) = %1", [
        1 => [$id, 'String'],
      ]);
      $this->_mailingID = $id;
      if (!empty($check)) {
        $this->_mailingIDIsHash = FALSE;
      }
      else {
        $this->_mailingIDIsHash = TRUE;
      }
    }
    else {
      $this->_mailingID = CRM_Utils_Request::retrieveValue('id', 'String', NULL, TRUE);
      $check = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_mailing WHERE CAST(id AS CHAR) = %1", [
        1 => [$this->_mailingID, 'String'],
      ]);
      if (!empty($check)) {
        $this->_mailingIDIsHash = FALSE;
      }
      else {
        $this->_mailingIDIsHash = TRUE;
      }
    }
  }

}
