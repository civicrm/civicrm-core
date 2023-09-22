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
class CRM_Contact_Page_ImageFile extends CRM_Core_Page {
  /**
   * Time to live (seconds).
   *
   * @var int
   *
   * 12 hours: 12 * 60 * 60 = 43200
   */
  private $ttl = 43200;

  /**
   * Run page.
   *
   * @throws \Exception
   */
  public function run() {
    if (!preg_match('/^[^\/]+\.(jpg|jpeg|png|gif)$/i', $_GET['photo'])) {
      throw new CRM_Core_Exception(ts('Malformed photo name'));
    }

    // FIXME Optimize performance of image_url query
    $sql = "SELECT id FROM civicrm_contact WHERE image_url like %1;";
    $params = [
      1 => ["%" . $_GET['photo'], 'String'],
    ];
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $cid = NULL;
    while ($dao->fetch()) {
      $cid = $dao->id;
    }
    if ($cid) {
      $config = CRM_Core_Config::singleton();
      $fileExtension = strtolower(pathinfo($_GET['photo'], PATHINFO_EXTENSION));
      $this->download(
        $config->customFileUploadDir . $_GET['photo'],
        'image/' . ($fileExtension == 'jpg' ? 'jpeg' : $fileExtension),
        $this->ttl
      );
    }
    else {
      header("HTTP/1.0 404 Not Found");
    }
    CRM_Utils_System::civiExit();
  }

  /**
   * Download image.
   *
   * @param string $file
   *   Local file path.
   * @param string $mimeType
   * @param int $ttl
   *   Time to live (seconds).
   */
  protected function download($file, $mimeType, $ttl) {
    if (!file_exists($file)) {
      header("HTTP/1.0 404 Not Found");
      return;
    }
    elseif (!is_readable($file)) {
      header('HTTP/1.0 403 Forbidden');
      return;
    }
    CRM_Utils_System::setHttpHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', CRM_Utils_Time::getTimeRaw() + $ttl));
    CRM_Utils_System::setHttpHeader("Content-Type", $mimeType);
    CRM_Utils_System::setHttpHeader("Content-Disposition", "inline; filename=\"" . basename($file) . "\"");
    CRM_Utils_System::setHttpHeader("Cache-Control", "max-age=$ttl, public");
    CRM_Utils_System::setHttpHeader('Pragma', 'public');
    readfile($file);
  }

}
