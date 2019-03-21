<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */

/**
 * This is base class for all ajax calls
 */
class CRM_Core_Page_AJAX {

  /**
   * Call generic ajax forms.
   *
   */
  public static function run() {
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
          $page = new $className();
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
   * Change is_quick_config priceSet to complex.
   *
   */
  public static function setIsQuickConfig() {
    $id = $context = NULL;
    if (!empty($_REQUEST['id'])) {
      $id = CRM_Utils_Type::escape($_REQUEST['id'], 'Integer');
    }

    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric');

    // return false if $id is null and
    // $context is not civicrm_event or civicrm_contribution_page
    if (!$id || !in_array($context, array('civicrm_event', 'civicrm_contribution_page'))) {
      return FALSE;
    }
    $priceSetId = CRM_Price_BAO_PriceSet::getFor($context, $id, NULL);
    if ($priceSetId) {
      $sql = "UPDATE
       civicrm_price_set cps
       INNER JOIN civicrm_price_set_entity cpse ON cps.id = cpse.price_set_id
       INNER JOIN {$context} ce ON cpse.entity_id = ce.id AND ce.id = %1
       SET cps.is_quick_config = 0, cps.financial_type_id = IF(cps.financial_type_id IS NULL, ce.financial_type_id, cps.financial_type_id)
      ";
      CRM_Core_DAO::executeQuery($sql, array(1 => array($id, 'Integer')));

      if ($context == 'civicrm_event') {
        CRM_Core_BAO_Discount::del($id, $context);
      }
    }

    CRM_Utils_JSON::output($priceSetId);
  }

  /**
   * Determine whether the request is for a valid class/method name.
   *
   * @param string $type
   *   'method'|'class'|''.
   * @param string $className
   *   'Class_Name'.
   * @param string $fnName
   *   Method name.
   *
   * @return bool
   */
  public static function checkAuthz($type, $className, $fnName = NULL) {
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
  public static function returnJsonResponse($response) {
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
      CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    }
    else {
      $output = "<textarea>$output</textarea>";
    }
    echo $output;
    CRM_Utils_System::civiExit();
  }

  /**
   * Set headers appropriate for a js file.
   *
   * @param int|NULL $ttl
   *   Time-to-live (seconds).
   */
  public static function setJsHeaders($ttl = NULL) {
    if ($ttl === NULL) {
      // Encourage browsers to cache for a long time - 1 year
      $ttl = 60 * 60 * 24 * 364;
    }
    CRM_Utils_System::setHttpHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $ttl));
    CRM_Utils_System::setHttpHeader('Content-Type', 'application/javascript');
    CRM_Utils_System::setHttpHeader('Cache-Control', "max-age=$ttl, public");
  }

  /**
   * Set defaults for sort and pager.
   *
   * @param int $defaultOffset
   * @param int $defaultRowCount
   * @param string $defaultSort
   * @param string $defaultsortOrder
   *
   * @return array
   */
  public static function defaultSortAndPagerParams($defaultOffset = 0, $defaultRowCount = 25, $defaultSort = NULL, $defaultsortOrder = 'asc') {
    $params = array(
      '_raw_values' => array(),
    );

    $sortMapper = array();
    if (isset($_GET['columns'])) {
      foreach ($_GET['columns'] as $key => $value) {
        $sortMapper[$key] = CRM_Utils_Type::validate($value['data'], 'MysqlColumnNameOrAlias');
      };
    }

    $offset = isset($_GET['start']) ? CRM_Utils_Type::validate($_GET['start'], 'Integer') : $defaultOffset;
    $rowCount = isset($_GET['length']) ? CRM_Utils_Type::validate($_GET['length'], 'Integer') : $defaultRowCount;
    // Why is the number of order by columns limited to 1?
    $sort = isset($_GET['order'][0]['column']) ? CRM_Utils_Array::value(CRM_Utils_Type::validate($_GET['order'][0]['column'], 'Integer'), $sortMapper) : $defaultSort;
    $sortOrder = isset($_GET['order'][0]['dir']) ? CRM_Utils_Type::validate($_GET['order'][0]['dir'], 'MysqlOrderByDirection') : $defaultsortOrder;

    if ($sort) {
      $params['sortBy'] = "{$sort} {$sortOrder}";

      $params['_raw_values']['sort'][0] = $sort;
      $params['_raw_values']['order'][0] = $sortOrder;
    }

    $params['offset'] = $offset;
    $params['rp'] = $rowCount;
    $params['page'] = ($offset / $rowCount) + 1;

    return $params;
  }

  /**
   * Validate ajax input parameters.
   *
   * @param array $requiredParams
   * @param array $optionalParams
   *
   * @return array
   */
  public static function validateParams($requiredParams = array(), $optionalParams = array()) {
    $params = array();

    foreach ($requiredParams as $param => $type) {
      $params[$param] = CRM_Utils_Type::validate(CRM_Utils_Array::value($param, $_GET), $type);
    }

    foreach ($optionalParams as $param => $type) {
      if (CRM_Utils_Array::value($param, $_GET)) {
        if (!is_array($_GET[$param])) {
          $params[$param] = CRM_Utils_Type::validate(CRM_Utils_Array::value($param, $_GET), $type);
        }
        else {
          foreach ($_GET[$param] as $index => $value) {
            $params[$param][$index] = CRM_Utils_Type::validate($value, $type);
          }
        }
      }
    }

    return $params;

  }

}
