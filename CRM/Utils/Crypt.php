<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Utils_Crypt {

  static function encrypt($string) {
    if (empty($string)) {
      return $string;
    }

    if (function_exists('mcrypt_module_open') &&
      defined('CIVICRM_SITE_KEY')
    ) {
      $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_ECB, '');
      // ECB mode - iv not needed - CRM-8198
      $iv  = '00000000000000000000000000000000';
      $ks  = mcrypt_enc_get_key_size($td);
      $key = substr(sha1(CIVICRM_SITE_KEY), 0, $ks);

      mcrypt_generic_init($td, $key, $iv);
      $string = mcrypt_generic($td, $string);
      mcrypt_generic_deinit($td);
      mcrypt_module_close($td);
    }
    return base64_encode($string);
  }

  static function decrypt($string) {
    if (empty($string)) {
      return $string;
    }

    $string = base64_decode($string);
    if (empty($string)) {
      return $string;
    }

    if (function_exists('mcrypt_module_open') &&
      defined('CIVICRM_SITE_KEY')
    ) {
      $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_ECB, '');
      // ECB mode - iv not needed - CRM-8198
      $iv  = '00000000000000000000000000000000';
      $ks  = mcrypt_enc_get_key_size($td);
      $key = substr(sha1(CIVICRM_SITE_KEY), 0, $ks);

      mcrypt_generic_init($td, $key, $iv);
      $string = rtrim(mdecrypt_generic($td, $string));
      mcrypt_generic_deinit($td);
      mcrypt_module_close($td);
    }

    return $string;
  }
}

