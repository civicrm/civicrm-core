<?php
/*
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * Class handles functions for JSON format
 */
class CRM_Utils_JSON {

  /**
   * Output json to the client.
   * @param mixed $input
   */
  public static function output($input) {
    header('Content-Type: application/json');
    echo json_encode($input);
    CRM_Utils_System::civiExit();
  }

  /**
   * Create JSON object.
   * @deprecated
   *
   * @param array $params
   *   Associated array, that needs to be.
   *                            converted to JSON array
   * @param string $identifier
   *   Identifier for the JSON array.
   *
   * @return string
   *   JSON array
   */
  public static function encode($params, $identifier = 'id') {
    $buildObject = array();
    foreach ($params as $value) {
      $name = addslashes($value['name']);
      $buildObject[] = "{ name: \"$name\", {$identifier}:\"{$value[$identifier]}\"}";
    }

    $jsonObject = '{ identifier: "' . $identifier . '", items: [' . implode(',', $buildObject) . ' ]}';

    return $jsonObject;
  }

  /**
   * Encode json format for flexigrid, NOTE: "id" should be present in $params for each row
   *
   * @param array $params
   *   Associated array of values rows.
   * @param int $page
   *   Page no for selector.
   * @param $total
   * @param array $selectorElements
   *   Selector rows.
   *
   * @return string
   *   json encoded string
   */
  public static function encodeSelector(&$params, $page, $total, $selectorElements) {
    $json = "";
    $json .= "{\n";
    $json .= "page: $page,\n";
    $json .= "total: $total,\n";
    $json .= "rows: [";
    $rc = FALSE;

    foreach ($params as $key => $value) {
      if ($rc) {
        $json .= ",";
      }
      $json .= "\n{";
      $json .= "id:'" . $value['id'] . "',";
      $json .= "cell:[";
      $addcomma = FALSE;
      foreach ($selectorElements as $element) {
        if ($addcomma) {
          $json .= ",";
        }
        $json .= "'" . addslashes($value[$element]) . "'";
        $addcomma = TRUE;
      }
      $json .= "]}";
      $rc = TRUE;
    }

    $json .= "]\n";
    $json .= "}";

    return $json;
  }

  /**
   * encode data for dataTable plugin.
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
    foreach ($params as $key => $value) {
      $addcomma = FALSE;
      $sOutput .= "[";
      foreach ($selectorElements as $element) {
        if ($addcomma) {
          $sOutput .= ",";
        }
        //CRM-7130 --lets addslashes to only double quotes,
        //since we are using it to quote the field value.
        //str_replace helps to provide a break for new-line
        $sOutput .= '"' . addcslashes(str_replace(array("\r\n", "\n", "\r"), '<br />', $value[$element]), '"\\') . '"';

        //remove extra spaces and tab character that breaks dataTable CRM-12551
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
