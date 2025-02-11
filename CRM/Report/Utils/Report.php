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
      $args = explode('/', CRM_Utils_System::currentPath());

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
      return [$templateInfo['id'] ?? NULL, $optionVal];
    }

    return FALSE;
  }

  /**
   * @param string $optionVal
   *
   * @return mixed
   */
  public static function getInstanceIDForValue($optionVal) {
    static $valId = [];

    if (!array_key_exists($optionVal, $valId)) {
      $sql = "
SELECT MIN(id) FROM civicrm_report_instance
WHERE  report_id = %1";

      $params = [1 => [$optionVal, 'String']];
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
    static $valId = [];

    // if $path is null, try to get it from url
    $path = self::getInstancePath();

    if ($path && !array_key_exists($path, $valId)) {
      $sql = "
SELECT MIN(id) FROM civicrm_report_instance
WHERE  TRIM(BOTH '/' FROM CONCAT(report_id, '/', name)) = %1";

      $params = [1 => [$path, 'String']];
      $valId[$path] = CRM_Core_DAO::singleValueQuery($sql, $params);
    }
    return $valId[$path] ?? NULL;
  }

  /**
   * @param string $urlValue
   * @param string $query
   * @param bool $absolute
   * @param int $instanceID
   * @param array $drilldownReport
   *
   * @return bool|string
   */
  public static function getNextUrl($urlValue, $query = 'reset=1', $absolute = FALSE, $instanceID = NULL, $drilldownReport = []) {
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
   * @param string $optionVal
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

    $params = [1 => [$optionVal, 'String']];
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
  public static function mailReport($fileContent, $instanceID = NULL, $outputMode = 'html', $attachments = []) {
    if (!$instanceID) {
      return FALSE;
    }

    list($domainEmailName,
      $domainEmailAddress
      ) = CRM_Core_BAO_Domain::getNameAndEmail();

    $params = ['id' => $instanceID];
    $instanceInfo = [];
    CRM_Core_DAO::commonRetrieve('CRM_Report_DAO_ReportInstance',
      $params,
      $instanceInfo
    );

    $params = [];
    $params['groupName'] = 'Report Email Sender';
    $params['from'] = '"' . $domainEmailName . '" <' . $domainEmailAddress . '>';
    //$domainEmailName;
    $params['toName'] = "";
    $params['toEmail'] = $instanceInfo['email_to'] ?? NULL;
    $params['cc'] = $instanceInfo['email_cc'] ?? NULL;
    $params['subject'] = $instanceInfo['email_subject'] ?? NULL;
    if (empty($instanceInfo['attachments'])) {
      $instanceInfo['attachments'] = [];
    }
    $params['attachments'] = array_merge($instanceInfo['attachments'] ?? [], $attachments);
    $params['text'] = '';
    $params['html'] = $fileContent;

    return CRM_Utils_Mail::send($params);
  }

  /**
   * @param CRM_Core_Form $form
   * @param array $rows
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

    // Output UTF BOM so that MS Excel copes with diacritics. This is recommended as
    // the Windows variant but is tested with MS Excel for Mac (Office 365 v 16.31)
    // and it continues to work on Libre Office, Numbers, Notes etc.
    $csv = "\xEF\xBB\xBF";

    // Add headers if this is the first row.
    $columnHeaders = array_keys($form->_columnHeaders);

    // Replace internal header names with friendly ones, where available.
    foreach ($columnHeaders as $header) {
      if (isset($form->_columnHeaders[$header])) {
        $title = $form->_columnHeaders[$header]['title'] ?? '';
        $headers[] = '"' . html_entity_decode(strip_tags($title)) . '"';
      }
    }
    // Add the headers.
    $csv .= implode($config->fieldSeparator,
        $headers
      ) . "\r\n";

    $displayRows = [];
    $value = NULL;
    foreach ($rows as $row) {
      foreach ($columnHeaders as $k => $v) {
        $value = $row[$v] ?? NULL;
        if (isset($value)) {
          // Remove HTML, unencode entities, and escape quotation marks.
          $value = str_replace('"', '""', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML401));

          if (($form->_columnHeaders[$v]['type'] ?? 0) & 4) {
            if (($form->_columnHeaders[$v]['group_by'] ?? NULL) == 'MONTH' ||
              ($form->_columnHeaders[$v]['group_by'] ?? NULL) == 'QUARTER'
            ) {
              $value = CRM_Utils_Date::customFormat($value, $config->dateformatPartial);
            }
            elseif (($form->_columnHeaders[$v]['group_by'] ?? NULL) == 'YEAR') {
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
          elseif (($form->_columnHeaders[$v]['type'] ?? NULL) == 1024 && !empty($row['civicrm_contribution_currency'])) {
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

    $arg = explode('/', CRM_Utils_System::currentPath());

    if (isset($arg[3]) && $arg[1] == 'report' && $arg[2] == 'instance' && CRM_Utils_Rule::positiveInteger($arg[3])) {
      return $arg[3];
    }
  }

  /**
   * @return string
   */
  public static function getInstancePath() {
    $arg = explode('/', CRM_Utils_System::currentPath());

    if (isset($arg[3]) && $arg[1] == 'report' && $arg[2] == 'instance') {
      unset($arg[0], $arg[1], $arg[2]);
      return trim(CRM_Utils_Type::escape(implode('/', $arg), 'String'), '/');
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

    $instanceValues = [];
    $params = ['id' => $instanceId];
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

    $instanceValues = [];
    $params = ['id' => $instanceId];
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
    $instanceId = $params['instanceId'] ?? NULL;

    // hack for now, CRM-8358
    $_REQUEST['instanceId'] = $instanceId;
    $_REQUEST['sendmail'] = $params['sendmail'] ?? 1;

    // if cron is run from terminal --output is reserved, and therefore we would provide another name 'format'
    $_REQUEST['output'] = $params['format'] ?? $params['output'] ?? 'pdf';
    $_REQUEST['reset'] = $params['reset'] ?? 1;

    $optionVal = self::getValueFromUrl($instanceId);
    $messages = ['Report Mail Triggered...'];
    if (empty($optionVal)) {
      $is_error = 1;
      $messages[] = 'Did not find a valid instance to execute';
    }
    else {
      $templateInfo = CRM_Core_OptionGroup::getRowValues('report_template', $optionVal, 'value');
      $obj = new CRM_Report_Page_Instance();
      $is_error = 0;
      if (str_contains($templateInfo['name'] ?? '', '_Form')) {
        $instanceInfo = [];
        CRM_Report_BAO_ReportInstance::retrieve(['id' => $instanceId], $instanceInfo);

        if (!empty($instanceInfo['title'])) {
          $obj->assign('reportTitle', $instanceInfo['title']);
        }
        else {
          $obj->assign('reportTitle', $templateInfo['label']);
        }

        $wrapper = new CRM_Utils_Wrapper();
        $arguments = [
          'urlToSession' => [
            [
              'urlVar' => 'instanceId',
              'type' => 'Positive',
              'sessionVar' => 'instanceId',
              'default' => 'null',
            ],
          ],
          'ignoreKey' => TRUE,
        ];
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
    }
    $result = [
      'is_error' => $is_error,
      'messages' => implode("\n", $messages),
    ];
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
  public static function getPreviewCriteriaQueryParams($defaults = [], $params = []) {
    static $query_string;
    if (!isset($query_string)) {
      if (!empty($params)) {
        $url_params = $op_values = $string_values = $process_params = [];

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
            $op_values[$basename] = $params["{$basename}_op"] ?? NULL;
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
   * @param string $reportUrl
   *
   * @return mixed
   */
  public static function getInstanceList($reportUrl) {
    static $instanceDetails = [];

    if (!array_key_exists($reportUrl, $instanceDetails)) {
      $instanceDetails[$reportUrl] = [];

      $sql = "
SELECT id, title FROM civicrm_report_instance
WHERE  report_id = %1";
      $params = [1 => [$reportUrl, 'String']];
      $result = CRM_Core_DAO::executeQuery($sql, $params);
      while ($result->fetch()) {
        $instanceDetails[$reportUrl][$result->id] = $result->title . " (ID: {$result->id})";
      }
    }
    return $instanceDetails[$reportUrl];
  }

}
