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

    $entityId = CRM_Utils_Request::retrieve('eid', 'Positive', $this, FALSE); // Entity ID (e.g. Contact ID)
    $fieldId = CRM_Utils_Request::retrieve('fid', 'Positive', $this, FALSE); // Field ID
    $fileId = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE); // File ID
    $fileName = CRM_Utils_Request::retrieve('filename', 'String', $this, FALSE);
    if (empty($fileName) && (empty($entityId) || empty($fileId))) {
      CRM_Core_Error::statusBounce("Cannot access file: Must pass either \"Filename\" or the combination of \"Entity ID\" + \"File ID\"");
    }

    if (empty($fileName)) {
      $hash = CRM_Utils_Request::retrieve('fcs', 'Alphanumeric', $this);
      if (!CRM_Core_BAO_File::validateFileHash($hash, $entityId, $fileId)) {
        CRM_Core_Error::statusBounce('URL for file is not valid');
      }

      list($path, $mimeType) = CRM_Core_BAO_File::path($fileId, $entityId);
    }
    else {
      if ($fileName !== basename($fileName)) {
        throw new CRM_Core_Exception("Malformed filename");
      }
      $mimeType = '';
      $path = CRM_Core_Config::singleton()->customFileUploadDir . $fileName;
    }
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
        CRM_Core_BAO_File::deleteFileReferences($fileId, $entityId, $fieldId);
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

}
