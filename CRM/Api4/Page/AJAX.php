<?php

class CRM_Api4_Page_AJAX extends CRM_Core_Page {

  /**
   * Handler for api4 ajax requests
   */
  public function run() {
    try {
      // Call multiple
      if (empty($this->urlPath[3])) {
        $calls = CRM_Utils_Request::retrieve('calls', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'POST', TRUE);
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
      $response = [
        'error_code' => $e->getCode(),
      ];
      if (CRM_Core_Permission::check('view debug output')) {
        $response['error_message'] = $e->getMessage();
        if (\Civi::settings()->get('backtrace')) {
          $response['backtrace'] = $e->getTrace();
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
