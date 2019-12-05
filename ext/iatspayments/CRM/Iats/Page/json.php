<?php

/**
 * @file
 */

/**
 *
 */
class CRM_Iats_Page_json {

  /**
   *
   */
  public function run() {
    // Generate json output from iats service calls.
    $request = $_POST;
    $pp_id = (int) $request['payment_processor_id'];
    if (empty($pp_id)) {
      return;
    }
    $params = array('version' => 3, 'sequential' => 1, 'id' => $pp_id, 'return' => 'user_name');
    $result = civicrm_api('PaymentProcessor', 'getvalue', $params);
    $request['agentCode'] = $result;
    $params = array('version' => 3, 'sequential' => 1, 'id' => $pp_id, 'return' => 'url_site');
    $result = civicrm_api('PaymentProcessor', 'getvalue', $params);
    $request['iats_domain'] = parse_url($result, PHP_URL_HOST);
    foreach (array('reset', 'q', 'IDS_request_uri', 'IDS_user_agent', 'payment_processor_id') as $key) {
      if (isset($request[$key])) {
        unset($request[$key]);
      }
    }
    $options = array();
    foreach (array('type', 'method', 'iats_domain') as $key) {
      if (isset($request[$key])) {
        $options[$key] = $request[$key];
        unset($request[$key]);
      }
    }
    $credentials = array();
    foreach (array('agentCode', 'password') as $key) {
      if (isset($request[$key])) {
        $credentials[$key] = $request[$key];
        unset($request[$key]);
      }
    }
    // TODO: bail here if I don't have enough for my service request
    // use the iATSService object for interacting with iATS.
    $iats = new CRM_Iats_iATSServiceRequest($options);
    $request['customerIPAddress'] = (function_exists('ip_address') ? ip_address() : $_SERVER['REMOTE_ADDR']);
    // Make the soap request.
    $response = $iats->request($credentials, $request);
    // Process the soap response into a readable result.
    if (!empty($response)) {
      $result = $iats->result($response);
    }
    else {
      $result = array('Invalid request');
    }
    // TODO: fix header
    // header('Content-Type: text/javascript');.
    echo json_encode(array_merge($result));
    exit;
  }

}
