<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * CRM_Core_Page_AJAX_Attachment defines an end-point for AJAX operations which upload file-attachments.
 *
 * To upload a new file, submit a POST (multi-part encoded) to "civicrm/ajax/attachment". Inputs:
 *  - POST['entity_table']: string
 *  - POST['entity_id']: int
 *  - FILES[*]: all of the files to attach to the entity
 *
 * The response is a JSON document. Foreach item in FILES, there's a corresponding record in the response which
 * describes the success or failure.
 *
 * Note: The permission requirements are determined by the underlying Attachment API.
 */
class CRM_Core_Page_AJAX_Attachment {

  public static function attachFile() {
    $result = self::_attachFile($_POST, $_FILES, $_SERVER);
    self::sendResponse($result);
  }

  /**
   * @param array $post (like global $_POST)
   * @param array $files (like global $_FILES)
   * @param array $server (like global $_SERVER)
   * @return array
   */
  public static function _attachFile($post, $files, $server) {
    $config = CRM_Core_Config::singleton();
    $results = array();

    foreach ($files as $key => $file) {
      if (!$config->debug && !self::isAJAX($server)) {
        require_once 'api/v3/utils.php';
        $results[$key] = civicrm_api3_create_error("SECURITY ALERT: Ajax requests can only be issued by javascript clients, eg. CRM.api3().",
          array(
            'IP' => $server['REMOTE_ADDR'],
            'level' => 'security',
            'referer' => $server['HTTP_REFERER'],
            'reason' => 'CSRF suspected',
          )
        );
      }
      elseif ($file['error']) {
        $results[$key] = civicrm_api3_create_error("Upload failed (code=" . $file['error'] . ")");
      }
      else {
        CRM_Core_Transaction::create(TRUE)
          ->run(function (CRM_Core_Transaction $tx) use ($key, $file, $post, &$results) {
            // We want check_permissions=1 while creating the DB record and check_permissions=0 while moving upload,
            // so split the work across two api calls.

            $params = array();
            if (isset($file['name'])) {
              $params['name'] = $file['name'];
            }
            if (isset($file['type'])) {
              $params['mime_type'] = $file['type'];
            }
            foreach (array('entity_table', 'entity_id', 'description') as $field) {
              if (isset($post[$field])) {
                $params[$field] = $post[$field];
              }
            }
            $params['version'] = 3;
            $params['check_permissions'] = 1;
            $params['content'] = '';
            $results[$key] = civicrm_api('Attachment', 'create', $params);

            if (!$results[$key]['is_error']) {
              $moveParams = array(
                'id' => $results[$key]['id'],
                'version' => 3,
                'options.move-file' => $file['tmp_name'],
                // note: in this second call, check_permissions==false
              );
              $moveResult = civicrm_api('Attachment', 'create', $moveParams);
              if ($moveResult['is_error']) {
                $results[$key] = $moveResult;
                $tx->rollback();
              }
            }
          });
      }
    }

    return $results;
  }

  /**
   * @param array $server (like global $_SERVER)
   * @return bool
   */
  public static function isAJAX($server) {
    return array_key_exists('HTTP_X_REQUESTED_WITH', $server) && $server['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest";
  }

  /**
   * @param array $result list of API responses, keyed by file
   */
  public static function sendResponse($result) {
    $isError = FALSE;
    foreach ($result as $item) {
      $isError = $isError || $item['is_error'];
    }

    if ($isError) {
      $sapi_type = php_sapi_name();
      if (substr($sapi_type, 0, 3) == 'cgi') {
        header("Status: 500 Internal Server Error");
      }
      else {
        header("HTTP/1.1 500 Internal Server Error");
      }
    }

    header('Content-Type: text/javascript');
    echo json_encode(array_merge($result));
    CRM_Utils_System::civiExit();
  }
}
