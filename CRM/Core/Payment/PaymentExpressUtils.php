<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
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


/*
 * PxPay Functionality Copyright (C) 2008 Lucas Baker, Logistic Information Systems Limited (Logis)
 * PxAccess Functionality Copyright (C) 2008 Eileen McNaughton
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * Grateful acknowledgements go to Donald Lobo for invaluable assistance
 * in creating this payment processor module
 */

/**
 * Class CRM_Core_Payment_PaymentExpressUtils
 */
class CRM_Core_Payment_PaymentExpressUtils {

  /**
   * @param $element
   * @param null $value
   *
   * @return string
   */
  static function _valueXml($element, $value = NULL) {
    $nl = "\n";

    if (is_array($element)) {
      $xml = '';
      foreach ($element as $elem => $value) {
          $xml .= self::_valueXml($elem, $value);
      }
      return $xml;
    }
    return "<" . $element . ">" . $value . "</" . $element . ">" . $nl;
  }

  /**
   * @param $xml
   * @param $name
   *
   * @return mixed
   */
  static function _xmlElement($xml, $name) {
    $value = preg_replace('/.*<' . $name . '[^>]*>(.*)<\/' . $name . '>.*/', '\1', $xml);
    return $value;
  }

  /**
   * @param $xml
   * @param $name
   *
   * @return mixed|null
   */
  static function _xmlAttribute($xml, $name) {
    $value = preg_replace('/<.*' . $name . '="([^"]*)".*>/', '\1', $xml);
    return $value != $xml ? $value : NULL;
  }

  /**
   * @param $query
   * @param $url
   *
   * @return resource
   */
  static function &_initCURL($query, $url) {
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    if (ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off') {
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, FALSE);
    }
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_SSLVERSION, 3);

    if (strtoupper(substr(@php_uname('s'), 0, 3)) === 'WIN') {
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'verifySSL'));
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'verifySSL') ? 2 : 0);
    }
    return $curl;
  }

}
