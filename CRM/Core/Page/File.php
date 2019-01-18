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
 * $Id$
 *
 */
class CRM_Core_Page_File extends CRM_Core_Page {

  /**
   * Run page.
   */
  public function run() {
    $action = CRM_Utils_Request::retrieve('action', 'String', $this);
    $download = CRM_Utils_Request::retrieve('download', 'Integer', $this, FALSE, 1);
    $disposition = $download == 0 ? 'inline' : 'download';

    $eid = CRM_Utils_Request::retrieve('eid', 'Positive', $this, TRUE);
    $fid = CRM_Utils_Request::retrieve('fid', 'Positive', $this, FALSE);
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $hash = CRM_Utils_Request::retrieve('fcs', 'Alphanumeric', $this);
    if (!self::validateFileHash($hash, $eid, $fid)) {
      CRM_Core_Error::statusBounce('URL for file is not valid');
    }

    list($path, $mimeType) = CRM_Core_BAO_File::path($id, $eid);
    $mimeType = CRM_Utils_Request::retrieveValue('mime-type', 'String', $mimeType, FALSE);

    if (!$path) {
      CRM_Core_Error::statusBounce('Could not retrieve the file');
    }

    $buffer = file_get_contents($path);
    if (!$buffer) {
      CRM_Core_Error::statusBounce('The file is either empty or you do not have permission to retrieve the file');
    }

    if ($action & CRM_Core_Action::DELETE) {
      if (CRM_Utils_Request::retrieve('confirmed', 'Boolean')) {
        CRM_Core_BAO_File::deleteFileReferences($id, $eid, $fid);
        CRM_Core_Session::setStatus(ts('The attached file has been deleted.'), ts('Complete'), 'success');

        $session = CRM_Core_Session::singleton();
        $toUrl = $session->popUserContext();
        CRM_Utils_System::redirect($toUrl);
      }
    }
    else {
      CRM_Utils_System::download(
        CRM_Utils_File::cleanFileName(basename($path)),
        $mimeType,
        $buffer,
        NULL,
        TRUE,
        $disposition
      );
    }
  }

  /**
   * Validate a file Hash
   * @param string $hash
   * @param int $eid Entity Id the file is attached to
   * @param int $fid File Id
   * @return bool
   */
  public static function validateFileHash($hash, $eid, $fid) {
    if (empty($hash)) {
      return TRUE;
    }
    $testHash = CRM_Core_BAO_File::generateFileHash($eid, $fid);
    if ($testHash == $hash) {
      return TRUE;
    }
    return FALSE;
  }

}
