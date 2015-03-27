<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Contact_Page_ImageFile extends CRM_Core_Page {
  /**
   * @var int Time to live (seconds).
   *
   * 12 hours: 12 * 60 * 60 = 43200
   */
  private $ttl = 43200;

  public function run() {
    if (!preg_match('/^[^\/]+\.(jpg|jpeg|png|gif)$/i', $_GET['photo'])) {
      CRM_Core_Error::fatal('Malformed photo name');
    }

    // FIXME Optimize performance of image_url query
    $sql = "SELECT id FROM civicrm_contact WHERE image_url like %1;";
    $params = array(
      1 => array("%" . $_GET['photo'], 'String'),
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $cid = $dao->id;
    }
    if ($cid) {
      $config = CRM_Core_Config::singleton();
      $this->download(
        $config->customFileUploadDir . $_GET['photo'],
        'image/' . pathinfo($_GET['photo'], PATHINFO_EXTENSION),
        $this->ttl
      );
      CRM_Utils_System::civiExit();
    }
    else {
      CRM_Core_Error::fatal('Photo does not exist');
    }
  }

  /**
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
    } elseif (!is_readable($file)) {
      header('HTTP/1.0 403 Forbidden');
      return;
    }
    header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', CRM_Utils_Time::getTimeRaw() + $ttl));
    header("Content-Type: $mimeType");
    header("Content-Disposition: inline; filename=\"" . basename($file) . "\"");
    header("Cache-Control: max-age=$ttl, public");
    header('Pragma: public');
    readfile($file);
  }

}
