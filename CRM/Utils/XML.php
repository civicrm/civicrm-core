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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
    // SimpleXMLElement
    $xml = FALSE;
    // string
    $error = FALSE;

    if (!file_exists($file)) {
      $error = 'File ' . $file . ' does not exist.';
    }
    else {
      $oldLibXMLErrors = libxml_use_internal_errors();
      libxml_use_internal_errors(TRUE);

      // Note that under obscure circumstances calling simplexml_load_file
      // hit https://bugs.php.net/bug.php?id=62577
      $string = file_get_contents($file);
      $xml = simplexml_load_string($string, 'SimpleXMLElement', LIBXML_NOCDATA);
      if ($xml === FALSE) {
        $error = self::formatErrors(libxml_get_errors());
      }

      libxml_use_internal_errors($oldLibXMLErrors);
    }

    return [$xml, $error];
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
    // SimpleXMLElement
    $xml = FALSE;
    // string
    $error = FALSE;

    $oldLibXMLErrors = libxml_use_internal_errors();
    libxml_use_internal_errors(TRUE);

    $xml = simplexml_load_string($string,
      'SimpleXMLElement', LIBXML_NOCDATA
    );
    if ($xml === FALSE) {
      $error = self::formatErrors(libxml_get_errors());
    }

    libxml_use_internal_errors($oldLibXMLErrors);

    return [$xml, $error];
  }

  /**
   * @param $errors
   *
   * @return string
   */
  protected static function formatErrors($errors) {
    $messages = [];

    foreach ($errors as $error) {
      if ($error->level != LIBXML_ERR_ERROR && $error->level != LIBXML_ERR_FATAL) {
        continue;
      }

      $parts = [];
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
    $arr = [];
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
