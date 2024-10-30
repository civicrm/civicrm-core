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
class CRM_Api4_Page_AJAX extends CRM_Core_Page {

  private $httpResponseCode;

  /**
   * Handler for api4 ajax requests
   */
  public function run() {
    $response = [];
    // `$this->urlPath` contains the http request path as an exploded array.
    // Path for single calls is `civicrm/ajax/api4/Entity/action` with `params` passed to $_REQUEST
    // or for multiple calls the path is `civicrm/ajax/api4` with `calls` passed to $_POST
    // Padding the array to avoid undefined index warnings when checking for single/multiple calls
    $this->urlPath = array_pad($this->urlPath, 5, '');

    // First check for problems with the request
    $error = $this->checkRequestMethod();
    if ($error) {
      $this->returnJSON($error);
    }

    // Two call formats. Which one was used? Note: CRM_Api4_Permission::check() and CRM_Api4_Page_AJAX::run() should have matching conditionals.
    if (empty($this->urlPath[3])) {
      // Received multi-call format
      $calls = CRM_Utils_Request::retrieve('calls', 'String', NULL, TRUE, NULL, 'POST');
      $calls = json_decode($calls, TRUE);
      foreach ($calls as $index => $call) {
        $response[$index] = call_user_func_array([$this, 'execute'], $call);
      }
    }
    else {
      // Received single-call format
      $entity = $this->urlPath[3];
      $action = $this->urlPath[4];
      $params = CRM_Utils_Request::retrieve('params', 'String');
      $params = $params ? json_decode($params, TRUE) : [];
      $index = CRM_Utils_Request::retrieve('index', 'String');
      $response = $this->execute($entity, $action, $params, $index);
    }

    $this->returnJSON($response);
  }

  /**
   * @return array|null
   */
  private function checkRequestMethod(): ?array {
    if (!CRM_Utils_REST::isWebServiceRequest() && !Civi::settings()->get('debug_enabled')) {
      $this->httpResponseCode = 400;
      Civi::log()->debug("SECURITY ALERT: Ajax requests can only be issued by javascript clients, eg. CRM.api4().",
        [
          'IP' => CRM_Utils_System::ipAddress(),
          'level' => 'security',
          'referer' => $_SERVER['HTTP_REFERER'],
          'reason' => 'CSRF suspected',
        ]
      );
      return [
        'error_code' => 400,
        'error_message' => "SECURITY ALERT: Ajax requests can only be issued by javascript clients, eg. CRM.api4().",
      ];

    }
    if ($_SERVER['REQUEST_METHOD'] == 'GET' &&
      ($this->urlPath[4] !== 'autocomplete' && strtolower(substr($this->urlPath[4], 0, 3)) !== 'get')
    ) {
      $this->httpResponseCode = 405;
      Civi::log()->debug("SECURITY: All requests that modify the database must be http POST, not GET.",
        [
          'IP' => CRM_Utils_System::ipAddress(),
          'level' => 'security',
          'referer' => $_SERVER['HTTP_REFERER'],
          'reason' => 'Destructive HTTP GET',
        ]
      );
      return [
        'error_code' => 405,
        'error_message' => "SECURITY: All requests that modify the database must be http POST, not GET.",
      ];
    }
    return NULL;
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
  private function execute(string $entity, string $action, array $params = [], $index = NULL) {
    $response = [];
    try {
      $params['checkPermissions'] = TRUE;
      // Handle numeric indexes later so we can get the count
      $itemAt = CRM_Utils_Type::validate($index, 'Integer', FALSE);
      $result = civicrm_api4($entity, $action, $params, isset($itemAt) ? NULL : $index);
      // Convert Result object into something more suitable for json
      $response = ['values' => isset($itemAt) ? $result->itemAt($itemAt) : (array) $result];
      // Add metadata from Result object
      foreach (get_class_vars(get_class($result)) as $key => $val) {
        $response[$key] = $result->$key;
      }
      unset($response['rowCount']);
      $response['count'] = $result->count();
      $response['countFetched'] = $result->countFetched();
      if (in_array('row_count', $params['select'] ?? [])) {
        // We can only return countMatched (whose value is independent of LIMIT clauses) if row_count was in the select.
        $response['countMatched'] = $result->count();
      }
      // If at least one call succeeded, we give a success code
      $this->httpResponseCode = 200;
    }
    catch (Exception $e) {
      $statusMap = [
        \Civi\API\Exception\UnauthorizedException::class => 403,
      ];
      $status = $statusMap[get_class($e)] ?? 500;
      // Send error code (but don't overwrite success code if there are multiple calls and one was successful)
      $this->httpResponseCode = $this->httpResponseCode ?: $status;
      if (CRM_Core_Permission::check('view debug output') || (method_exists($e, 'getErrorData') && ($e->getErrorData()['show_detailed_error'] ?? FALSE))) {
        $response['error_code'] = $e->getCode();
        $response['error_message'] = $e->getMessage();
        if (!empty($params['debug']) && CRM_Core_Permission::check('view debug output')) {
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
      else {
        $error_id = rtrim(chunk_split(CRM_Utils_String::createRandom(12, CRM_Utils_String::ALPHANUMERIC), 4, '-'), '-');
        $response['error_code'] = '1';
        $response['error_message']  = ts('Sorry an error occurred and your request was not completed. (Error ID: %1)', [
          1 => $error_id,
        ]);
        \Civi::log()->debug('AJAX Error ({error_id}): failed with exception', [
          'error_id' => $error_id,
          'exception' => $e,
        ]);
      }
      $response['status'] = $status;
    }
    return $response;
  }

  /**
   * Output JSON response to the client
   *
   * @param array $response
   * @return void
   */
  private function returnJSON(array $response): void {
    http_response_code($this->httpResponseCode);
    CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    echo json_encode($response);
    CRM_Utils_System::civiExit();
  }

}
