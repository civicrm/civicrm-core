<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This is base class for all ajax calls
 */
class CRM_Core_Page_AJAX {

  /**
   * function to call generic ajax forms
   *
   * @static
   * @access public
   */
  static function run() {
    $className = CRM_Utils_Type::escape($_REQUEST['class_name'], 'String');
    $type = '';
    if (!empty($_REQUEST['type'])) {
      $type = CRM_Utils_Type::escape($_REQUEST['type'], 'String');
    }

    if (!$className) {
      CRM_Core_Error::fatal(ts('Invalid className: %1', array(1 => $className)));
    }

    $fnName = NULL;
    if (isset($_REQUEST['fn_name'])) {
      $fnName = CRM_Utils_Type::escape($_REQUEST['fn_name'], 'String');
    }

    if (!self::checkAuthz($type, $className, $fnName)) {
      CRM_Utils_System::civiExit();
    }

    switch ($type) {
      case 'method':
        call_user_func(array($className, $fnName));
        break;

      case 'page':
      case 'class':
      case '':
        // FIXME: This is done to maintain current wire protocol, but it might be
        // simpler to just require different 'types' for pages and forms
        if (preg_match('/^CRM_[a-zA-Z0-9]+_Page_Inline_/', $className)) {
          $page = new $className;
          $page->run();
        }
        else {
          $wrapper = new CRM_Utils_Wrapper();
          $wrapper->run($className);
        }
        break;
      default:
        CRM_Core_Error::debug_log_message('Unsupported inline request type: ' . var_export($type, TRUE));
    }
    CRM_Utils_System::civiExit();
  }

  /**
   * function to change is_quick_config priceSet to complex
   *
   * @static
   * @access public
   */
  static function setIsQuickConfig() {
    $id = $context = NULL;
    if (!empty($_REQUEST['id'])) {
      $id = CRM_Utils_Type::escape($_REQUEST['id'], 'Integer');
    }

    if (!empty($_REQUEST['context'])) {
      $context = CRM_Utils_Type::escape($_REQUEST['context'], 'String');
    }
    // return false if $id is null and
    // $context is not civicrm_event or civicrm_contribution_page
    if (!$id || !in_array($context, array('civicrm_event', 'civicrm_contribution_page'))) {
      return false;
    }
    $priceSetId = CRM_Price_BAO_PriceSet::getFor($context, $id, NULL);
    if ($priceSetId) {
      $result = CRM_Price_BAO_PriceSet::setIsQuickConfig($priceSetId, 0);
      if ($context == 'civicrm_event') {
        $sql = "UPDATE
          civicrm_price_set cps
          INNER JOIN civicrm_discount cd ON cd.price_set_id = cps.id
          SET cps.is_quick_config = 0
          WHERE cd.entity_id = (%1) AND cd.entity_table = 'civicrm_event' ";
        $params = array(1 => array($id, 'Integer'));
        CRM_Core_DAO::executeQuery($sql, $params);
        CRM_Core_BAO_Discount::del($id, $context);
      }
    }
    if (!$result) {
      $priceSetId = null;
    }
    CRM_Utils_JSON::output($priceSetId);
  }

  /**
   * Determine whether the request is for a valid class/method name.
   *
   * @param string $type 'method'|'class'|''
   * @param string $className 'Class_Name'
   * @param string $fnName method name
   *
   * @return bool
   */
  static function checkAuthz($type, $className, $fnName = null) {
    switch ($type) {
      case 'method':
        if (!preg_match('/^CRM_[a-zA-Z0-9]+_Page_AJAX$/', $className)) {
          return FALSE;
        }
        if (!preg_match('/^[a-zA-Z0-9]+$/', $fnName)) {
          return FALSE;
        }

        // ensure that function exists
        return method_exists($className, $fnName);

      case 'page':
      case 'class':
      case '':
        if (!preg_match('/^CRM_[a-zA-Z0-9]+_(Page|Form)_Inline_[a-zA-Z0-9]+$/', $className)) {
          return FALSE;
        }
        return class_exists($className);
      default:
        return FALSE;
    }
  }

  /**
   * Outputs the CiviCRM standard json-formatted page/form response
   * @param array|string $response
   */
  static function returnJsonResponse($response) {
    // Allow lazy callers to not wrap content in an array
    if (is_string($response)) {
      $response = array('content' => $response);
    }
    // Add session variables to response
    $session = CRM_Core_Session::singleton();
    $response += array(
      'status' => 'success',
      'userContext' => htmlspecialchars_decode($session->readUserContext()),
      'title' => CRM_Utils_System::$title,
    );
    // crmMessages will be automatically handled by our ajax preprocessor
    // @see js/Common.js
    if ($session->getStatus(FALSE)) {
      $response['crmMessages'] = $session->getStatus(TRUE);
    }
    $output = json_encode($response);

    // CRM-11831 @see http://www.malsup.com/jquery/form/#file-upload
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
      header('Content-Type: application/json');
    }
    else {
      $output = "<textarea>$output</textarea>";
    }
    echo $output;
    CRM_Utils_System::civiExit();
  }

  /**
   * Set headers appropriate for a js file
   */
  static function setJsHeaders() {
    // Encourage browsers to cache for a long time - 1 year
    $year = 60*60*24*364;
    header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + $year));
    header('Content-Type:	application/javascript');
    header("Cache-Control: max-age=$year, public");
  }

  /**
   * Send autocomplete results to the client. Input can be a simple or nested array.
   * @param array $results - If nested array, also provide:
   * @param string $val - array key to use as the value
   * @param string $key - array key to use as the key
   * @deprecated
   */
  static function autocompleteResults($results, $val='label', $key='id') {
    $output = array();
    if (is_array($results)) {
      foreach ($results as $k => $v) {
        if (is_array($v)) {
          echo $v[$val] . '|' . $v[$key] . "\n";
        }
        else {
          echo "$v|$k\n";
        }
      }
    }
    CRM_Utils_System::civiExit();
  }
}

