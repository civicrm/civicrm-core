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
  public static function _valueXml($element, $value = NULL) {
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
   * @param string $name
   *
   * @return mixed
   */
  public static function _xmlElement($xml, $name) {
    $value = preg_replace('/.*<' . $name . '[^>]*>(.*)<\/' . $name . '>.*/', '\1', $xml);
    return $value;
  }

  /**
   * @param $xml
   * @param string $name
   *
   * @return mixed|null
   */
  public static function _xmlAttribute($xml, $name) {
    $value = preg_replace('/<.*' . $name . '="([^"]*)".*>/', '\1', $xml);
    return $value != $xml ? $value : NULL;
  }

  /**
   * @param $query
   * @param $url
   *
   * @return resource
   */
  public static function &_initCURL($query, $url) {
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
    curl_setopt($curl, CURLOPT_SSLVERSION, 0);

    if (strtoupper(substr(@php_uname('s'), 0, 3)) === 'WIN') {
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, Civi::settings()->get('verifySSL'));
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, Civi::settings()->get('verifySSL') ? 2 : 0);
    }
    return $curl;
  }

}
