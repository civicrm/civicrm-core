<?php
/**
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
function civicrm_api3_prevem_login($params) {
  $prevemUrl = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME, 'prevem_url');

  $prevemURL = !empty($prevemUrl) ? CRM_Utils_URL::mask($prevemUrl, array('user', 'pass')) : NULL;
  if (!$prevemURL) {
    return civicrm_api3_create_error("prevemURL is not configured. Go to Administer>CiviMail>CiviMail Component Settings to configure prevemURL");
  }
  $prevemConsumer = parse_url($prevemUrl, PHP_URL_USER);
  $prevemSecret = parse_url($prevemUrl, PHP_URL_PASS);
  /** TODO Parse $prevemUrl. Send login request to get token. **/
  /** To send login request, see eg http://stackoverflow.com/questions/2445276/how-to-post-data-in-php-using-file-get-contents **/

  $postdata = http_build_query(
    array(
      'email' => $prevemConsumer . "@foo.com",
      'password' => $prevemSecret,
    )
  );

  $opts = array(
    'http' =>
    array(
      'method'  => 'POST',
      'header'  => 'Content-type: application/x-www-form-urlencoded',
      'content' => $postdata,
    ),
  );

  $context  = stream_context_create($opts);
  $result = file_get_contents($prevemURL . '/api/Users/login', FALSE, $context);

  if ($result === FALSE) {
    return civicrm_api3_create_error("Failed to login. Check if Preview Manager is running on " . $prevemURL);
  }
  else {
    $accessToken = json_decode($result)->{'id'};
    if (!$accessToken) {
      return civicrm_api3_create_error("Failed to parse access token. Check if Preview Manager is running on " . $prevemURL);
    }
    $returnValues = array(
      'url' => $prevemURL,
      'consumerId' => $prevemConsumer,
      'token' => $accessToken,
      'password' => $prevemSecret,
    );
    return civicrm_api3_create_success($returnValues, $params, 'Prevem', 'login');
  }
}
