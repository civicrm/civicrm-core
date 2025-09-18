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

/**
 * Class handles functions for JSON format
 */
class CRM_Utils_JSON {

  /**
   * Safely encodes a variable that will be printed inside a `<script>` tag.
   *
   * See https://lab.civicrm.org/dev/core/-/issues/6080
   *
   * @param mixed $input
   * @return int|string
   */
  public static function encodeScriptVar($input) {
    return json_encode($input, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  }

  /**
   * Output json to the client.
   * @param mixed $input
   */
  public static function output($input) {
    if (CIVICRM_UF === 'UnitTests') {
      throw new CRM_Core_Exception_PrematureExitException('civiExit called', $input);
    }
    CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    echo json_encode($input, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    CRM_Utils_System::civiExit();
  }

  /**
   * Test whether the input string is valid JSON.
   * @param string $str
   * @return boolean
   */
  public static function isValidJSON($str) {
    json_decode($str);
    return json_last_error() == JSON_ERROR_NONE;
  }

  /**
   * Do not use this function. See CRM-16353.
   * @deprecated
   *
   * @param array $params
   *   Associated array of row elements.
   * @param int $sEcho
   *   Datatable needs this to make it more secure.
   * @param int $iTotal
   *   Total records.
   * @param int $iFilteredTotal
   *   Total records on a page.
   * @param array $selectorElements
   *   Selector elements.
   * @return string
   */
  public static function encodeDataTableSelector($params, $sEcho, $iTotal, $iFilteredTotal, $selectorElements) {
    $sOutput = '{';
    $sOutput .= '"sEcho": ' . intval($sEcho) . ', ';
    $sOutput .= '"iTotalRecords": ' . $iTotal . ', ';
    $sOutput .= '"iTotalDisplayRecords": ' . $iFilteredTotal . ', ';
    $sOutput .= '"aaData": [ ';
    foreach ((array) $params as $key => $value) {
      $addcomma = FALSE;
      $sOutput .= "[";
      foreach ($selectorElements as $element) {
        if ($addcomma) {
          $sOutput .= ",";
        }
        // CRM-7130 --lets addslashes to only double quotes,
        // since we are using it to quote the field value.
        // str_replace helps to provide a break for new-line
        $sOutput .= '"' . addcslashes(str_replace(["\r\n", "\n", "\r"], '<br />', ($value[$element] ?? '')), '"\\') . '"';

        // remove extra spaces and tab character that breaks dataTable CRM-12551
        $sOutput = preg_replace("/\s+/", " ", $sOutput);
        $addcomma = TRUE;
      }
      $sOutput .= "],";
    }
    $sOutput = substr_replace($sOutput, "", -1);
    $sOutput .= '] }';

    return $sOutput;
  }

}
