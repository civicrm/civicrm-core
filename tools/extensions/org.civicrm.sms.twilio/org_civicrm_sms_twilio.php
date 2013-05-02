<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */
class org_civicrm_sms_twilio extends CRM_SMS_Provider {

  /**
   * api type to use to send a message
   * @var	string
   */
  protected $_apiType = 'http';

  /**
   * provider details
   * @var	string
   */
  protected $_providerInfo = array();

  /**
   * Curl handle resource id
   *
   */
  protected $_ch;

  public $_apiURL = "https://api.twilio.com/";

  protected $_messageType = array(
  );

  protected $_messageStatus = array(
  );

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = array();

  /**
   * Constructor
   *
   * Create and auth a Twilio session.
   * This is not needed for Twilio
   *
   * @return void
   */ 
  function __construct($provider = array(
     ), $skipAuth = TRUE) {
    // initialize vars
    $this->_apiType = CRM_Utils_Array::value('api_type', $provider, 'http');
    $this->_providerInfo = $provider;

    if ($skipAuth) {
      return TRUE;
    }

    // first create the curl handle

    /**
     * Reuse the curl handle
     */
    $this->_ch = curl_init();
    if (!$this->_ch || !is_resource($this->_ch)) {
      return PEAR::raiseError('Cannot initialise a new curl handle.');
    }

    $this->authenticate();
  }

  /**
   * singleton function used to manage this object
   *
   * @return object
   * @static
   *
   */
  static function &singleton($providerParams = array(
    ), $force = FALSE) {
    $providerID = CRM_Utils_Array::value('provider_id', $providerParams);
    $skipAuth   = $providerID ? FALSE : TRUE;
    $cacheKey   = (int) $providerID;

    if (!isset(self::$_singleton[$cacheKey]) || $force) {
      $provider = array();
      if ($providerID) {
        $provider = CRM_SMS_BAO_Provider::getProviderInfo($providerID);
      }
      self::$_singleton[$cacheKey] = new org_civicrm_sms_twilio($provider, $skipAuth);
    }
    return self::$_singleton[$cacheKey];
  }

  /**
   * Authenticate to the Twilio Server.
   * Not needed in Twilio
   * @return boolean TRUE
   * @access public
   * @since 1.1
   */
  function authenticate() { 
      return (TRUE);
  }

  function formURLPostData($url, $id = NULL) {
    $url = $this->_providerInfo['api_url'] . $url;
    $postData = array();
    return array($url, $postData);
  }

  /**
   * Send an SMS Message via the Twilio API Server
   *
   * @param array the message with a to/from/text
   *
   * @return mixed true on success or PEAR_Error object
   * @access public
   */
  function send($recipients, $header, $message, $jobID = NULL) {
    if ($this->_apiType = 'http') {
      list($url, $postData) = $this->formURLPostData("/2010-04-01/Accounts/{$this->_providerInfo['username']}/SMS/Messages.xml");
      $auth = $this->_providerInfo['username'] . ':' . $this->_providerInfo['password'];
      if (array_key_exists('From', $this->_providerInfo['api_params'])) {
        $postData['From'] = $this->_providerInfo['api_params']['From'];
      }
      $postData['To'] = $header['To'];
      $postData['Body'] = $message;
      
      $response = $this->curl($url, $postData, $auth);
	  
      if (PEAR::isError($response)) {
        return $response;
      }
	  
      $send = simplexml_load_string($response['data']);
      $sid = $send->SMSMessage->Sid;
      if (!empty($sid)) {
        $this->createActivity($sid, $message, $header, $jobID);
        return $sid;
      }
      else {
        $errMsg = $send->RestException->Message
          . ' For more information, see '
          . $send->RestException->MoreInfo;
        return PEAR::raiseError(
          $errMsg,
          null,
          PEAR_ERROR_RETURN
        );
      }
    }
  }

  function callback() {
  	return TRUE;
  }

  function inbound() {
    $like      = "";
    $fromPhone = $this->retrieve('From', 'String');
    return parent::processInbound($fromPhone, $this->retrieve('Body', 'String'), NULL, $this->retrieve('SmsSid', 'String'));
  }
  
  /**
   * Perform curl stuff
   *
   * @param   string  URL to call
   * @param   string  HTTP Post Data
   * @param   string  Authorization string composed of Account SID and Secret Key
   
   * @return  mixed   HTTP/XML response body or PEAR Error Object
   * @access	private
   */
  function curl($url, $postData, $auth) {
    $this->_fp = tmpfile();
    curl_setopt($this->_ch, CURLOPT_URL, $url);
    curl_setopt($this->_ch, CURLOPT_POST, 1);
    curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($this->_ch, CURLOPT_USERPWD, $auth);
    curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);

    $status = curl_exec($this->_ch);
    
    $response['http_code'] = curl_getinfo($this->_ch, CURLINFO_HTTP_CODE);
    
    if (empty($response['http_code'])) {
      return PEAR::raiseError('No HTTP Status Code was returned.', null, PEAR_ERROR_RETURN);
    }
    elseif ($response['http_code'] === 0) {
      return PEAR::raiseError('Cannot connect to the Twilio API Server.', null, PEAR_ERROR_RETURN);
    }

    $response['data'] = $status;
    asort($response);
    
    return ($response);
  }
}
