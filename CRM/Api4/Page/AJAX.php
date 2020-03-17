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
 * $Id$
 *
 */
class CRM_Api4_Page_AJAX extends CRM_Core_Page {

  /**
   * Handler for api4 ajax requests
   */
  public function run() {
    $config = CRM_Core_Config::singleton();
    if (!$config->debug && (!array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) ||
        $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest"
      )
    ) {
      $response = [
        'error_code' => 401,
        'error_message' => "SECURITY ALERT: Ajax requests can only be issued by javascript clients, eg. CRM.api4().",
      ];
      Civi::log()->debug("SECURITY ALERT: Ajax requests can only be issued by javascript clients, eg. CRM.api4().",
        [
          'IP' => $_SERVER['REMOTE_ADDR'],
          'level' => 'security',
          'referer' => $_SERVER['HTTP_REFERER'],
          'reason' => 'CSRF suspected',
        ]
      );
      CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
      echo json_encode($response);
      CRM_Utils_System::civiExit();
    }
    if ($_SERVER['REQUEST_METHOD'] == 'GET' &&
      strtolower(substr($this->urlPath[4], 0, 3)) != 'get') {
      $response = [
        'error_code' => 400,
        'error_message' => "SECURITY: All requests that modify the database must be http POST, not GET.",
      ];
      Civi::log()->debug("SECURITY: All requests that modify the database must be http POST, not GET.",
        [
          'IP' => $_SERVER['REMOTE_ADDR'],
          'level' => 'security',
          'referer' => $_SERVER['HTTP_REFERER'],
          'reason' => 'Destructive HTTP GET',
        ]
      );
      CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
      echo json_encode($response);
      CRM_Utils_System::civiExit();
    }
    try {
      // Call multiple
      if (empty($this->urlPath[3])) {
        $calls = CRM_Utils_Request::retrieve('calls', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'POST');
        $calls = json_decode($calls, TRUE);
        $response = [];
        foreach ($calls as $index => $call) {
          $response[$index] = call_user_func_array([$this, 'execute'], $call);
        }
      }
      // Call single
      else {
        $entity = $this->urlPath[3];
        $action = $this->urlPath[4];
        $params = CRM_Utils_Request::retrieve('params', 'String');
        $params = $params ? json_decode($params, TRUE) : [];
        $index = CRM_Utils_Request::retrieve('index', 'String');
        $response = $this->execute($entity, $action, $params, $index);
      }
    }
    catch (Exception $e) {
      http_response_code(500);
      $response = [];
      if (CRM_Core_Permission::check('view debug output')) {
        $response['error_code'] = $e->getCode();
        $response['error_message'] = $e->getMessage();
        if (!empty($params['debug'])) {
          if (method_exists($e, 'getUserInfo')) {
            $response['debug']['info'] = $e->getUserInfo();
          }
          $cause = method_exists($e, 'getCause') ? $e->getCause() : $e;
          if ($cause instanceof \DB_Error) {
            $response['debug']['db_error'] = \DB::errorMessage($cause->getCode());
            $response['debug']['sql'][] = $cause->getDebugInfo();
          }
          if (\Civi::settings()->get('backtrace')) {
            // Would prefer getTrace() but that causes json_encode to bomb
            $response['debug']['backtrace'] = $e->getTraceAsString();
          }
        }
      }
    }
    CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    echo json_encode($response);
    CRM_Utils_System::civiExit();
  }

  /**
   * Run api call & prepare result for json encoding
   *
   * @param string $entity
   * @param string $action
   * @param array $params
   * @param string $index
   * @return array
   */
  protected function execute($entity, $action, $params = [], $index = NULL) {
    $params['checkPermissions'] = TRUE;

    // Handle numeric indexes later so we can get the count
    $itemAt = CRM_Utils_Type::validate($index, 'Integer', FALSE);

    $result = civicrm_api4($entity, $action, $params, isset($itemAt) ? NULL : $index);

    // Convert arrayObject into something more suitable for json
    $vals = ['values' => isset($itemAt) ? $result->itemAt($itemAt) : (array) $result];
    foreach (get_class_vars(get_class($result)) as $key => $val) {
      $vals[$key] = $result->$key;
    }
    $vals['count'] = $result->count();
    return $vals;
  }

}
