<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
 * CRM_Core_Page_AJAX_Attachment defines an end-point for AJAX operations which upload file-attachments.
 *
 * To upload a new file, submit a POST (multi-part encoded) to "civicrm/ajax/attachment". Inputs:
 *  - POST['entity_table']: string
 *  - POST['entity_id']: int
 *  - POST['attachment_token']: string
 *  - FILES[*]: all of the files to attach to the entity
 *
 * The response is a JSON document. Foreach item in FILES, there's a corresponding record in the response which
 * describes the success or failure.
 *
 * Note: The permission requirements are determined by the underlying Attachment API.
 */
class CRM_Core_Page_AJAX_Attachment {

  const ATTACHMENT_TOKEN_TTL = 10800; // 3hr; 3*60*60

  /**
   * (Page Callback)
   */
  public static function attachFile() {
    $result = self::_attachFile($_POST, $_FILES, $_SERVER);
    self::sendResponse($result);
  }

  /**
   * @param array $post
   *   Like global $_POST.
   * @param array $files
   *   Like global $_FILES.
   * @param array $server
   *   Like global $_SERVER.
   * @return array
   */
  public static function _attachFile($post, $files, $server) {
    $config = CRM_Core_Config::singleton();
    $results = array();

    foreach ($files as $key => $file) {
      if (!$config->debug && !self::checkToken($post['crm_attachment_token'])) {
        require_once 'api/v3/utils.php';
        $results[$key] = civicrm_api3_create_error("SECURITY ALERT: Attaching files via AJAX requires a recent, valid token.",
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
   * @param array $result
   *   List of API responses, keyed by file.
   */
  public static function sendResponse($result) {
    $isError = FALSE;
    foreach ($result as $item) {
      $isError = $isError || $item['is_error'];
    }

    if ($isError) {
      $sapi_type = php_sapi_name();
      if (substr($sapi_type, 0, 3) == 'cgi') {
        CRM_Utils_System::setHttpHeader("Status", "500 Internal Server Error");
      }
      else {
        header("HTTP/1.1 500 Internal Server Error");
      }
    }

    CRM_Utils_JSON::output(array_merge($result));
  }

  /**
   * @return string
   */
  public static function createToken() {
    $signer = new CRM_Utils_Signer(CRM_Core_Key::privateKey(), array('for', 'ts'));
    $ts = CRM_Utils_Time::getTimeRaw();
    return $signer->sign(array(
      'for' => 'crmAttachment',
      'ts' => $ts,
    )) . ';;;' . $ts;
  }

  /**
   * @param string $token
   *   A token supplied by the user.
   * @return bool
   *   TRUE if the token is valid for submitting attachments
   * @throws Exception
   */
  public static function checkToken($token) {
    list ($signature, $ts) = explode(';;;', $token);
    $signer = new CRM_Utils_Signer(CRM_Core_Key::privateKey(), array('for', 'ts'));
    if (!is_numeric($ts) || CRM_Utils_Time::getTimeRaw() > $ts + self::ATTACHMENT_TOKEN_TTL) {
      return FALSE;
    }
    return $signer->validate($signature, array(
      'for' => 'crmAttachment',
      'ts' => $ts,
    ));
  }

}
