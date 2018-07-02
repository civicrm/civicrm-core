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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 * $Id$
 *
 */
class CRM_Report_Utils_Report {

  /**
   * @param int $instanceID
   *
   * @return null|string
   */
  public static function getValueFromUrl($instanceID = NULL) {
    if ($instanceID) {
      $optionVal = CRM_Core_DAO::getFieldValue('CRM_Report_DAO_ReportInstance',
        $instanceID,
        'report_id'
      );
    }
    else {
      $config = CRM_Core_Config::singleton();
      $args = explode('/', $_GET[$config->userFrameworkURLVar]);

      // remove 'civicrm/report' from args
      array_shift($args);
      array_shift($args);

      // put rest of argument back in the form of url, which is how value
      // is stored in option value table
      $optionVal = implode('/', $args);
    }
    return $optionVal;
  }

  /**
   * @param int $instanceID
   *
   * @return array|bool
   */
  public static function getValueIDFromUrl($instanceID = NULL) {
    $optionVal = self::getValueFromUrl($instanceID);

    if ($optionVal) {
      $templateInfo = CRM_Core_OptionGroup::getRowValues('report_template', "{$optionVal}", 'value');
      return array(CRM_Utils_Array::value('id', $templateInfo), $optionVal);
    }

    return FALSE;
  }

  /**
   * @param $optionVal
   *
   * @return mixed
   */
  public static function getInstanceIDForValue($optionVal) {
    static $valId = array();

    if (!array_key_exists($optionVal, $valId)) {
      $sql = "
SELECT MIN(id) FROM civicrm_report_instance
WHERE  report_id = %1";

      $params = array(1 => array($optionVal, 'String'));
      $valId[$optionVal] = CRM_Core_DAO::singleValueQuery($sql, $params);
    }
    return $valId[$optionVal];
  }

  /**
   * @param null $path
   *
   * @return mixed
   */
  public static function getInstanceIDForPath($path = NULL) {
    static $valId = array();

    // if $path is null, try to get it from url
    $path = self::getInstancePath();

    if ($path && !array_key_exists($path, $valId)) {
      $sql = "
SELECT MIN(id) FROM civicrm_report_instance
WHERE  TRIM(BOTH '/' FROM CONCAT(report_id, '/', name)) = %1";

      $params = array(1 => array($path, 'String'));
      $valId[$path] = CRM_Core_DAO::singleValueQuery($sql, $params);
    }
    return CRM_Utils_Array::value($path, $valId);
  }

  /**
   * @param $urlValue
   * @param string $query
   * @param bool $absolute
   * @param int $instanceID
   * @param array $drilldownReport
   *
   * @return bool|string
   */
  public static function getNextUrl($urlValue, $query = 'reset=1', $absolute = FALSE, $instanceID = NULL, $drilldownReport = array()) {
    if ($instanceID) {
      $drilldownInstanceID = FALSE;
      if (array_key_exists($urlValue, $drilldownReport)) {
        $drilldownInstanceID = CRM_Core_DAO::getFieldValue('CRM_Report_DAO_ReportInstance', $instanceID, 'drilldown_id', 'id');
      }

      if (!$drilldownInstanceID) {
        $drilldownInstanceID = self::getInstanceIDForValue($urlValue);
      }

      if ($drilldownInstanceID) {
        return CRM_Utils_System::url("civicrm/report/instance/{$drilldownInstanceID}",
          "{$query}", $absolute
        );
      }
      else {
        return FALSE;
      }
    }
    else {
      return CRM_Utils_System::url("civicrm/report/" . trim($urlValue, '/'),
        $query, $absolute
      );
    }
  }

  /**
   * get instance count for a template.
   * @param $optionVal
   *
   * @return int|null|string
   */
  public static function getInstanceCount($optionVal) {
    if (empty($optionVal)) {
      return 0;
    }

    $sql = "
SELECT count(inst.id)
FROM   civicrm_report_instance inst
WHERE  inst.report_id = %1";

    $params = array(1 => array($optionVal, 'String'));
    $count = CRM_Core_DAO::singleValueQuery($sql, $params);
    return $count;
  }

  /**
   * @param $fileContent
   * @param int $instanceID
   * @param string $outputMode
   * @param array $attachments
   *
   * @return bool
   */
  public static function mailReport($fileContent, $instanceID = NULL, $outputMode = 'html', $attachments = array()) {
    if (!$instanceID) {
      return FALSE;
    }

    list($domainEmailName,
      $domainEmailAddress
      ) = CRM_Core_BAO_Domain::getNameAndEmail();

    $params = array('id' => $instanceID);
    $instanceInfo = array();
    CRM_Core_DAO::commonRetrieve('CRM_Report_DAO_ReportInstance',
      $params,
      $instanceInfo
    );

    $params = array();
    $params['groupName'] = 'Report Email Sender';
    $params['from'] = '"' . $domainEmailName . '" <' . $domainEmailAddress . '>';
    //$domainEmailName;
    $params['toName'] = "";
    $params['toEmail'] = CRM_Utils_Array::value('email_to', $instanceInfo);
    $params['cc'] = CRM_Utils_Array::value('email_cc', $instanceInfo);
    $params['subject'] = CRM_Utils_Array::value('email_subject', $instanceInfo);
    if (empty($instanceInfo['attachments'])) {
      $instanceInfo['attachments'] = array();
    }
    $params['attachments'] = array_merge(CRM_Utils_Array::value('attachments', $instanceInfo), $attachments);
    $params['text'] = '';
    $params['html'] = $fileContent;

    return CRM_Utils_Mail::send($params);
  }

  /**
   * @param CRM_Core_Form $form
   * @param $rows
   */
  public static function export2csv(&$form, &$rows) {
    //Mark as a CSV file.
    CRM_Utils_System::setHttpHeader('Content-Type', 'text/csv');

    //Force a download and name the file using the current timestamp.
    $datetime = date('Ymd-Gi', $_SERVER['REQUEST_TIME']);
    CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename=Report_' . $datetime . '.csv');
    echo self::makeCsv($form, $rows);
    CRM_Utils_System::civiExit();
  }

  /**
   * Utility function for export2csv and CRM_Report_Form::endPostProcess
   * - make CSV file content and return as string.
   *
   * @param CRM_Core_Form $form
   * @param array $rows
   *
   * @return string
   */
  public static function makeCsv(&$form, &$rows) {
    $config = CRM_Core_Config::singleton();
    $csv = '';

    // Add headers if this is the first row.
    $columnHeaders = array_keys($form->_columnHeaders);

    // Replace internal header names with friendly ones, where available.
    foreach ($columnHeaders as $header) {
      if (isset($form->_columnHeaders[$header])) {
        $headers[] = '"' . html_entity_decode(strip_tags($form->_columnHeaders[$header]['title'])) . '"';
      }
    }
    // Add the headers.
    $csv .= implode($config->fieldSeparator,
        $headers
      ) . "\r\n";

    $displayRows = array();
    $value = NULL;
    foreach ($rows as $row) {
      foreach ($columnHeaders as $k => $v) {
        $value = CRM_Utils_Array::value($v, $row);
        if (isset($value)) {
          // Remove HTML, unencode entities, and escape quotation marks.
          $value = str_replace('"', '""', html_entity_decode(strip_tags($value)));

          if (CRM_Utils_Array::value('type', $form->_columnHeaders[$v]) & 4) {
            if (CRM_Utils_Array::value('group_by', $form->_columnHeaders[$v]) == 'MONTH' ||
              CRM_Utils_Array::value('group_by', $form->_columnHeaders[$v]) == 'QUARTER'
            ) {
              $value = CRM_Utils_Date::customFormat($value, $config->dateformatPartial);
            }
            elseif (CRM_Utils_Array::value('group_by', $form->_columnHeaders[$v]) == 'YEAR') {
              $value = CRM_Utils_Date::customFormat($value, $config->dateformatYear);
            }
            elseif ($form->_columnHeaders[$v]['type'] == 12) {
              // This is a datetime format
              $value = CRM_Utils_Date::customFormat($value, '%Y-%m-%d %H:%i');
            }
            else {
              $value = CRM_Utils_Date::customFormat($value, '%Y-%m-%d');
            }
          }
          // Note the reference to a specific field does not belong in this generic class & does not work on all reports.
          // @todo - fix this properly rather than just supressing the en-otice. Repeat transaction report is a good example.
          elseif (CRM_Utils_Array::value('type', $form->_columnHeaders[$v]) == 1024 && !empty($row['civicrm_contribution_currency'])) {
            $value = CRM_Utils_Money::format($value, $row['civicrm_contribution_currency']);
          }
          $displayRows[$v] = '"' . $value . '"';
        }
        else {
          $displayRows[$v] = "";
        }
      }
      // Add the data row.
      $csv .= implode($config->fieldSeparator,
          $displayRows
        ) . "\r\n";
    }

    return $csv;
  }

  /**
   * @return mixed
   */
  public static function getInstanceID() {

    $config = CRM_Core_Config::singleton();
    $arg = explode('/', $_GET[$config->userFrameworkURLVar]);

    if ($arg[1] == 'report' &&
      CRM_Utils_Array::value(2, $arg) == 'instance'
    ) {
      if (CRM_Utils_Rule::positiveInteger($arg[3])) {
        return $arg[3];
      }
    }
  }

  /**
   * @return string
   */
  public static function getInstancePath() {
    $config = CRM_Core_Config::singleton();
    $arg = explode('/', $_GET[$config->userFrameworkURLVar]);

    if ($arg[1] == 'report' &&
      CRM_Utils_Array::value(2, $arg) == 'instance'
    ) {
      unset($arg[0], $arg[1], $arg[2]);
      $path = trim(CRM_Utils_Type::escape(implode('/', $arg), 'String'), '/');
      return $path;
    }
  }

  /**
   * @param int $instanceId
   *
   * @return bool
   */
  public static function isInstancePermissioned($instanceId) {
    if (!$instanceId) {
      return TRUE;
    }

    $instanceValues = array();
    $params = array('id' => $instanceId);
    CRM_Core_DAO::commonRetrieve('CRM_Report_DAO_ReportInstance',
      $params,
      $instanceValues
    );

    if (!empty($instanceValues['permission']) &&
      (!(CRM_Core_Permission::check($instanceValues['permission']) ||
        CRM_Core_Permission::check('administer Reports')
      ))
    ) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Check if the user can view a report instance based on their role(s)
   *
   * @instanceId string $str the report instance to check
   *
   * @param int $instanceId
   *
   * @return bool
   *   true if yes, else false
   */
  public static function isInstanceGroupRoleAllowed($instanceId) {
    if (!$instanceId) {
      return TRUE;
    }

    $instanceValues = array();
    $params = array('id' => $instanceId);
    CRM_Core_DAO::commonRetrieve('CRM_Report_DAO_ReportInstance',
      $params,
      $instanceValues
    );
    //transform grouprole to array
    if (!empty($instanceValues['grouprole'])) {
      $grouprole_array = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        $instanceValues['grouprole']
      );
      if (!CRM_Core_Permission::checkGroupRole($grouprole_array) &&
        !CRM_Core_Permission::check('administer Reports')
      ) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * @param array $params
   *
   * @return array
   */
  public static function processReport($params) {
    $instanceId = CRM_Utils_Array::value('instanceId', $params);

    // hack for now, CRM-8358
    $_REQUEST['instanceId'] = $instanceId;
    $_REQUEST['sendmail'] = CRM_Utils_Array::value('sendmail', $params, 1);

    // if cron is run from terminal --output is reserved, and therefore we would provide another name 'format'
    $_REQUEST['output'] = CRM_Utils_Array::value('format', $params, CRM_Utils_Array::value('output', $params, 'pdf'));
    $_REQUEST['reset'] = CRM_Utils_Array::value('reset', $params, 1);

    $optionVal = self::getValueFromUrl($instanceId);
    $messages = array("Report Mail Triggered...");

    $templateInfo = CRM_Core_OptionGroup::getRowValues('report_template', $optionVal, 'value');
    $obj = new CRM_Report_Page_Instance();
    $is_error = 0;
    if (strstr(CRM_Utils_Array::value('name', $templateInfo), '_Form')) {
      $instanceInfo = array();
      CRM_Report_BAO_ReportInstance::retrieve(array('id' => $instanceId), $instanceInfo);

      if (!empty($instanceInfo['title'])) {
        $obj->assign('reportTitle', $instanceInfo['title']);
      }
      else {
        $obj->assign('reportTitle', $templateInfo['label']);
      }

      $wrapper = new CRM_Utils_Wrapper();
      $arguments = array(
        'urlToSession' => array(
          array(
            'urlVar' => 'instanceId',
            'type' => 'Positive',
            'sessionVar' => 'instanceId',
            'default' => 'null',
          ),
        ),
        'ignoreKey' => TRUE,
      );
      $messages[] = $wrapper->run($templateInfo['name'], NULL, $arguments);
    }
    else {
      $is_error = 1;
      if (!$instanceId) {
        $messages[] = 'Required parameter missing: instanceId';
      }
      else {
        $messages[] = 'Did not find valid instance to execute';
      }
    }

    $result = array(
      'is_error' => $is_error,
      'messages' => implode("\n", $messages),
    );
    return $result;
  }

  /**
   * Build a URL query string containing all report filter criteria that are
   * stipulated in $_GET or in a report Preview, but which haven't yet been
   * saved in the report instance.
   *
   * @param array $defaults
   *   The report criteria that aren't coming in as submitted form values, as in CRM_Report_Form::_defaults.
   * @param array $params
   *   All effective report criteria, as in CRM_Report_Form::_params.
   *
   * @return string
   *   URL query string
   */
  public static function getPreviewCriteriaQueryParams($defaults = array(), $params = array()) {
    static $query_string;
    if (!isset($query_string)) {
      if (!empty($params)) {
        $url_params = $op_values = $string_values = $process_params = array();

        // We'll only use $params that are different from what's in $default.
        foreach ($params as $field_name => $field_value) {
          if (!array_key_exists($field_name, $defaults) || $defaults[$field_name] != $field_value) {
            $process_params[$field_name] = $field_value;
          }
        }
        // Criteria stipulated in $_GET will be in $defaults even if they're not
        // saved, so we can't easily tell if they're saved or not. So just include them.
        $process_params += $_GET;

        // All $process_params should be passed on if they have an effective value
        // (in other words, there's no point in propagating blank filters).
        foreach ($process_params as $field_name => $field_value) {
          $suffix_position = strrpos($field_name, '_');
          $suffix = substr($field_name, $suffix_position);
          $basename = substr($field_name, 0, $suffix_position);
          if ($suffix == '_min' || $suffix == '_max' ||
            $suffix == '_from' || $suffix == '_to' ||
            $suffix == '_relative'
          ) {
            // For these types, we only keep them if they have a value.
            if (!empty($field_value)) {
              $url_params[$field_name] = $field_value;
            }
          }
          elseif ($suffix == '_value') {
            // These filters can have an effect even without a value
            // (e.g., values for 'nll' and 'nnll' ops are blank),
            // so store them temporarily and examine below.
            $string_values[$basename] = $field_value;
            $op_values[$basename] = CRM_Utils_Array::value("{$basename}_op", $params);
          }
          elseif ($suffix == '_op') {
            // These filters can have an effect even without a value
            // (e.g., values for 'nll' and 'nnll' ops are blank),
            // so store them temporarily and examine below.
            $op_values[$basename] = $field_value;
            $string_values[$basename] = $params["{$basename}_value"];
          }
        }

        // Check the *_value and *_op criteria and include them if
        // they'll have an effective value.
        foreach ($op_values as $basename => $field_value) {
          if ($field_value == 'nll' || $field_value == 'nnll') {
            // 'nll' and 'nnll' filters should be included even with empty values.
            $url_params["{$basename}_op"] = $field_value;
          }
          elseif ($string_values[$basename]) {
            // Other filters are only included if they have a value.
            $url_params["{$basename}_op"] = $field_value;
            $url_params["{$basename}_value"] = (is_array($string_values[$basename]) ? implode(',', $string_values[$basename]) : $string_values[$basename]);
          }
        }
        $query_string = http_build_query($url_params);
      }
      else {
        $query_string = '';
      }
    }
    return $query_string;
  }

  /**
   * @param $reportUrl
   *
   * @return mixed
   */
  public static function getInstanceList($reportUrl) {
    static $instanceDetails = array();

    if (!array_key_exists($reportUrl, $instanceDetails)) {
      $instanceDetails[$reportUrl] = array();

      $sql = "
SELECT id, title FROM civicrm_report_instance
WHERE  report_id = %1";
      $params = array(1 => array($reportUrl, 'String'));
      $result = CRM_Core_DAO::executeQuery($sql, $params);
      while ($result->fetch()) {
        $instanceDetails[$reportUrl][$result->id] = $result->title . " (ID: {$result->id})";
      }
    }
    return $instanceDetails[$reportUrl];
  }

}
