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

    // Entity ID (e.g. Contact ID)
    $entityId = CRM_Utils_Request::retrieve('eid', 'Positive', $this, FALSE);
    // Field ID
    $fieldId = CRM_Utils_Request::retrieve('fid', 'Positive', $this, FALSE);
    // File ID
    $fileId = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
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
      if (!CRM_Utils_File::isValidFileName($fileName)) {
        throw new CRM_Core_Exception("Malformed filename");
      }
      $mimeType = '';
      $path = CRM_Core_Config::singleton()->customFileUploadDir . $fileName;
    }

    if (!$path) {
      CRM_Core_Error::statusBounce('Could not retrieve the file');
    }

    if (empty($mimeType)) {
      $passedInMimeType = self::convertBadMimeAliasTypes(CRM_Utils_Request::retrieveValue('mime-type', 'String', $mimeType, FALSE));
      if (!in_array($passedInMimeType, explode(',', Civi::settings()->get('requestableMimeTypes')))) {
        throw new CRM_Core_Exception("Supplied mime-type is not accepted");
      }
      $extension = CRM_Utils_File::getExtensionFromPath($path);
      $candidateExtensions = CRM_Utils_File::getAcceptableExtensionsForMimeType($passedInMimeType);
      if (!in_array(strtolower($extension), array_map('strtolower', $candidateExtensions))) {
        throw new CRM_Core_Exception("Supplied mime-type does not match file extension");
      }
      // Now that we have validated mime-type supplied as much as possible lets now set the MimeType variable/
      $mimeType = $passedInMimeType;
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

  /**
   * Translate one mime type to another.
   *
   * Certain non-standard/weird MIME types have been common. Unfortunately, because
   * of the way this controller is used, the weird types may baked-into URLs.
   * We clean these up for compatibility.
   *
   * @param string $type
   *   Ex: 'image/jpg'
   * @return string
   *   Ex: 'image/jpeg'.
   */
  protected static function convertBadMimeAliasTypes($type) {
    $badTypes = [
      // Before PNG format was ubiquitous, it was image/x-png?
      'image/x-png' => 'image/png',

      // People see "image/gif" and "image/png" and wrongly guess "image/jpg"?
      'image/jpg' => 'image/jpeg',
      'image/tif' => 'image/tiff',
      'image/svg' => 'image/svg+xml',

      // StackExchange attributes "pjpeg" to some quirk in an old version of IE?
      'image/pjpeg' => 'image/jpeg',

    ];
    return isset($badTypes[$type]) ? $badTypes[$type] : $type;
  }

}
