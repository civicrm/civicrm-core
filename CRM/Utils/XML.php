<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Utils_XML {

  /**
   * Read a well-formed XML file
   *
   * @param $file
   *
   * @return array
   *   (0 => SimpleXMLElement|FALSE, 1 => errorMessage|FALSE)
   */
  public static function parseFile($file) {
    $xml = FALSE; // SimpleXMLElement
    $error = FALSE; // string

    if (!file_exists($file)) {
      $error = 'File ' . $file . ' does not exist.';
    }
    else {
      $oldLibXMLErrors = libxml_use_internal_errors();
      libxml_use_internal_errors(TRUE);

      $xml = simplexml_load_file($file,
        'SimpleXMLElement', LIBXML_NOCDATA
      );
      if ($xml === FALSE) {
        $error = self::formatErrors(libxml_get_errors());
      }

      libxml_use_internal_errors($oldLibXMLErrors);
    }

    return array($xml, $error);
  }

  /**
   * Read a well-formed XML file
   *
   * @param $string
   *
   * @return array
   *   (0 => SimpleXMLElement|FALSE, 1 => errorMessage|FALSE)
   */
  public static function parseString($string) {
    $xml = FALSE; // SimpleXMLElement
    $error = FALSE; // string

    $oldLibXMLErrors = libxml_use_internal_errors();
    libxml_use_internal_errors(TRUE);

    $xml = simplexml_load_string($string,
      'SimpleXMLElement', LIBXML_NOCDATA
    );
    if ($xml === FALSE) {
      $error = self::formatErrors(libxml_get_errors());
    }

    libxml_use_internal_errors($oldLibXMLErrors);

    return array($xml, $error);
  }

  /**
   * @param $errors
   *
   * @return string
   */
  protected static function formatErrors($errors) {
    $messages = array();

    foreach ($errors as $error) {
      if ($error->level != LIBXML_ERR_ERROR && $error->level != LIBXML_ERR_FATAL) {
        continue;
      }

      $parts = array();
      if ($error->file) {
        $parts[] = "File=$error->file";
      }
      $parts[] = "Line=$error->line";
      $parts[] = "Column=$error->column";
      $parts[] = "Code=$error->code";

      $messages[] = implode(" ", $parts) . ": " . trim($error->message);
    }

    return implode("\n", $messages);
  }

  /**
   * Convert an XML element to an array.
   *
   * @param $obj
   *   SimpleXMLElement.
   *
   * @return array
   */
  public static function xmlObjToArray($obj) {
    $arr = array();
    if (is_object($obj)) {
      $obj = get_object_vars($obj);
    }
    if (is_array($obj)) {
      foreach ($obj as $i => $v) {
        if (is_object($v) || is_array($v)) {
          $v = self::xmlObjToArray($v);
        }
        if (empty($v)) {
          $arr[$i] = NULL;
        }
        else {
          $arr[$i] = $v;
        }
      }
    }
    return $arr;
  }

}
