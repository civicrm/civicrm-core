<?php

/**
 * Copyright (C) 2006 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/* This class is instantiated everytime any notification or 
  * order processing commands are received.
  * 
  * It has a SendReq function to post different requests to the Google Server
  * Send functions are provided for most of the commands that are supported
  * by the server 
  * Refer demo/responsehandlerdemo.php for different use case scenarios 
  * for this code
  */
class GoogleResponse {
  var $merchant_id;
  var $merchant_key;
  var $server_url;
  var $schema_url;
  var $base_url;
  var $checkout_url;
  var $checkout_diagnose_url;
  var $request_url;
  var $request_diagnose_url;

  var $response;
  var $root;
  var $data;
  var $xml_parser;
  function GoogleResponse($id, $key, $response, $server_type = "checkout") {
    $this->merchant_id = $id;
    $this->merchant_key = $key;

    if ($server_type == "sandbox") {

      $this->server_url = "https://sandbox.google.com/checkout/";
    }
    else $this->server_url = "https://checkout.google.com/";

    $this->schema_url = "http://checkout.google.com/schema/2";
    $this->base_url = $this->server_url . "cws/v2/Merchant/" . $this->merchant_id;
    $this->checkout_url = $this->base_url . "/checkout";
    $this->checkout_diagnose_url = $this->base_url . "/checkout/diagnose";
    $this->request_url = $this->base_url . "/request";
    $this->request_diagnose_url = $this->base_url . "/request/diagnose";

    $this->response = $response;

    if (strpos(__FILE__, ':') !== FALSE) {

      $path_delimiter = ';';
    }
    else $path_delimiter = ':';

    ini_set('include_path', ini_get('include_path') . $path_delimiter . '.');
    require_once ('xml-processing/xmlparser.php');
    $this->xml_parser = new XmlParser($response);
    $this->root       = $this->xml_parser->GetRoot();
    $this->data       = $this->xml_parser->GetData();
  }

  function HttpAuthentication($headers) {
    if (isset($headers['Authorization'])) {
      $auth_encode = $headers['Authorization'];
      $auth = base64_decode(substr($auth_encode,
          strpos($auth_encode, " ") + 1
        ));
      $compare_mer_id = substr($auth, 0, strpos($auth, ":"));
      $compare_mer_key = substr($auth, strpos($auth, ":") + 1);
    }
    else {
      return FALSE;
    }
    if ($compare_mer_id != $this->merchant_id ||
      $compare_mer_key != $this->merchant_key
    ) return FALSE;
    return TRUE;
  }

  function SendChargeOrder($google_order, $amount = '', $message_log) {
    $postargs = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                   <charge-order xmlns=\"" . $this->schema_url . "\" google-order-number=\"" . $google_order . "\">";
    if ($amount != '') {
      $postargs .= "<amount currency=\"USD\">" . $amount . "</amount>";
    }
    $postargs .= "</charge-order>";
    return $this->SendReq($this->request_url, $this->GetAuthenticationHeaders(),
      $postargs, $message_log
    );
  }

  function SendRefundOrder($google_order, $amount, $reason, $comment, $message_log) {
    $postargs = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                   <refund-order xmlns=\"" . $this->schema_url . "\" google-order-number=\"" . $google_order . "\">
                   <reason>" . $reason . "</reason>
                   <amount currency=\"USD\">" . htmlentities($amount) . "</amount>
                   <comment>" . htmlentities($comment) . "</comment>
                  </refund-order>";
    return $this->SendReq($this->request_url, $this->GetAuthenticationHeaders(),
      $postargs, $message_log
    );
  }

  function SendCancelOrder($google_order, $reason, $comment, $message_log) {
    $postargs = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                   <cancel-order xmlns=\"" . $this->schema_url . "\" google-order-number=\"" . $google_order . "\">
                   <reason>" . htmlentities($reason) . "</reason>
                   <comment>" . htmlentities($comment) . "</comment>
                  </cancel-order>";
    return $this->SendReq($this->request_url, $this->GetAuthenticationHeaders(),
      $postargs, $message_log
    );
  }

  function SendTrackingData($google_order, $carrier, $tracking_no, $message_log) {
    $postargs = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                   <add-tracking-data xmlns=\"" . $this->schema_url . "\" google-order-number=\"" . $google_order . "\">
                   <tracking-data>
                   <carrier>" . htmlentities($carrier) . "</carrier>
                   <tracking-number>" . $tracking_no . "</tracking-number>
                   </tracking-data>
                   </add-tracking-data>";
    return $this->SendReq($this->request_url, $this->GetAuthenticationHeaders(),
      $postargs, $message_log
    );
  }

  function SendMerchantOrderNumber($google_order, $merchant_order, $message_log) {
    $postargs = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                   <add-merchant-order-number xmlns=\"" . $this->schema_url . "\" google-order-number=\"" . $google_order . "\">
                     <merchant-order-number>" . $merchant_order . "</merchant-order-number>
                   </add-merchant-order-number>";
    return $this->SendReq($this->request_url, $this->GetAuthenticationHeaders(),
      $postargs, $message_log
    );
  }

  function SendBuyerMessage($google_order, $message, $send_mail = "true", $message_log) {
    $postargs = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                   <send-buyer-message xmlns=\"" . $this->schema_url . "\" google-order-number=\"" . $google_order . "\">
                     <message>" . $message . "</message>
                     <send-mail>" . $send_mail . "</send-mail>
                   </send-buyer-message>";
    return $this->SendReq($this->request_url, $this->GetAuthenticationHeaders(),
      $postargs, $message_log
    );
  }

  function SendProcessOrder($google_order, $message_log) {
    $postargs = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                  <process-order xmlns=\"" . $this->schema_url . "\" google-order-number=\"" . $google_order . "\"/> ";
    return $this->SendReq($this->request_url, $this->GetAuthenticationHeaders(),
      $postargs, $message_log
    );
  }

  function SendDeliverOrder($google_order, $carrier, $tracking_no, $send_mail = "true", $message_log) {
    $postargs = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                   <deliver-order xmlns=\"" . $this->schema_url . "\" google-order-number=\"" . $google_order . "\">
                   <tracking-data>
                   <carrier>" . htmlentities($carrier) . "</carrier>
                   <tracking-number>" . $tracking_no . "</tracking-number>
                   </tracking-data>
                   <send-email>" . $send_mail . "</send-email>
                   </deliver-order>";
    return $this->SendReq($this->request_url, $this->GetAuthenticationHeaders(),
      $postargs, $message_log
    );
  }

  function SendArchiveOrder($google_order, $message_log) {
    $postargs = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                   <archive-order xmlns=\"" . $this->schema_url . "\" google-order-number=\"" . $google_order . "\"/>";
    return $this->SendReq($this->request_url, $this->GetAuthenticationHeaders(),
      $postargs, $message_log
    );
  }

  function SendUnarchiveOrder($google_order, $message_log) {
    $postargs = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                   <unarchive-order xmlns=\"" . $this->schema_url . "\" google-order-number=\"" . $google_order . "\"/>";
    return $this->SendReq($this->request_url, $this->GetAuthenticationHeaders(),
      $postargs, $message_log
    );
  }

  function ProcessMerchantCalculations($merchant_calc) {
    $result = $merchant_calc->GetXML();
    echo $result;
  }

  function GetAuthenticationHeaders() {
    $headers = array();
    $headers[] = "Authorization: Basic " . base64_encode(
      $this->merchant_id . ':' . $this->merchant_key
    );
    $headers[] = "Content-Type: application/xml";
    $headers[] = "Accept: application/xml";
    return $headers;
  }

  function SendReq($url, $header_arr, $postargs, $message_log) {
    // Get the curl session object
    $session = curl_init($url);

    // Set the POST options.
    curl_setopt($session, CURLOPT_POST, TRUE);
    curl_setopt($session, CURLOPT_HTTPHEADER, $header_arr);
    curl_setopt($session, CURLOPT_POSTFIELDS, $postargs);
    curl_setopt($session, CURLOPT_HEADER, TRUE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);

    // Do the POST and then close the session
    $response = curl_exec($session);
    if (curl_errno($session)) {
      die(curl_error($session));
    }
    else {
      curl_close($session);
    }

    // Get HTTP Status code from the response
    $status_code = array();
    preg_match('/\d\d\d/', $response, $status_code);

    // Check for errors
    switch ($status_code[0]) {
      case 200:
        // Success
        break;

      case 503:
        die('Error 503: Service unavailable.');
        break;

      case 403:
        die('Error 403: Forbidden.');
        break;

      case 400:
        die('Error 400: Bad request.');
        break;

      default:
        echo $response;
        die('Error :' . $status_code[0]);
    }
    fwrite($message_log, sprintf("\n\r%s:- %s\n", date("D M j G:i:s T Y"),
        $response
      ));
  }

  function SendAck() {
    $acknowledgment = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . "<notification-acknowledgment xmlns=\"" . $this->schema_url . "\"/>";
    echo $acknowledgment;
  }
}

