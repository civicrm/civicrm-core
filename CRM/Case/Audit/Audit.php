<?php

/**
 * Class CRM_Case_Audit_Audit
 */
class CRM_Case_Audit_Audit {
  private $auditConfig;
  private $xmlString;

  /**
   * @param $xmlString
   * @param $confFilename
   */
  public function __construct($xmlString, $confFilename) {
    $this->xmlString = $xmlString;
    $this->auditConfig = new CRM_Case_Audit_AuditConfig($confFilename);
  }

  /**
   * @param bool $printReport
   *
   * @return array
   */
  public function getActivities($printReport = FALSE) {
    $retval = array();

    /*
     * Loop through the activities in the file and add them to the appropriate region array.
     */

    $doc = new DOMDocument();

    if ($doc->loadXML($this->xmlString)) {
      $regionList = $this->auditConfig->getRegions();

      $ifBlanks = $this->auditConfig->getIfBlanks();

      $includeAll = $doc->getElementsByTagName("IncludeActivities")->item(0)->nodeValue;
      $includeAll = ($includeAll == 'All');

      $activityindex = 0;
      $activityList = $doc->getElementsByTagName("Activity");

      $caseActivities = array();
      $activityStatusType = array();

      foreach ($activityList as $activity) {
        $retval[$activityindex] = array();

        $ifBlankReplacements = array();

        $completed  = FALSE;
        $sortValues = array('1970-01-01');
        $category   = '';
        $fieldindex = 1;
        $fields     = $activity->getElementsByTagName("Field");
        foreach ($fields as $field) {
          $datatype_elements = $field->getElementsByTagName("Type");
          $datatype = $datatype_elements->item(0)->nodeValue;

          $label_elements = $field->getElementsByTagName("Label");
          $label = $label_elements->item(0)->nodeValue;

          $value_elements = $field->getElementsByTagName("Value");
          $value = $value_elements->item(0)->nodeValue;

          $category_elements = $field->getElementsByTagName("Category");
          if (!empty($category_elements->length)) {
            $category = $category_elements->item(0)->nodeValue;
          }

          // Based on the config file, does this field's label and value indicate a completed activity?
          if ($label == $this->auditConfig->getCompletionLabel() && $value == $this->auditConfig->getCompletionValue()) {
            $completed = TRUE;
          }

          // Based on the config file, does this field's label match the one to use for sorting activities?
          if (in_array($label, $this->auditConfig->getSortByLabels())) {
            $sortValues[$label] = $value;
          }

          foreach ($regionList as $region) {
            // Based on the config file, is this field a potential replacement for another?
            if (!empty($ifBlanks[$region])) {
              if (in_array($label, $ifBlanks[$region])) {
                $ifBlankReplacements[$label] = $value;
              }
            }

            if ($this->auditConfig->includeInRegion($label, $region)) {
              $retval[$activityindex][$region][$fieldindex] = array();
              $retval[$activityindex][$region][$fieldindex]['label'] = $label;
              $retval[$activityindex][$region][$fieldindex]['datatype'] = $datatype;
              $retval[$activityindex][$region][$fieldindex]['value'] = $value;
              if ($datatype == 'Date') {
                $retval[$activityindex][$region][$fieldindex]['includeTime'] = $this->auditConfig->includeTime($label, $region);
              }

              //CRM-4570
              if ($printReport) {
                if (!in_array($label, array(
                  'Activity Type', 'Status'))) {
                  $caseActivities[$activityindex][$fieldindex] = array();
                  $caseActivities[$activityindex][$fieldindex]['label'] = $label;
                  $caseActivities[$activityindex][$fieldindex]['datatype'] = $datatype;
                  $caseActivities[$activityindex][$fieldindex]['value'] = $value;
                }
                else {
                  $activityStatusType[$activityindex][$fieldindex] = array();
                  $activityStatusType[$activityindex][$fieldindex]['label'] = $label;
                  $activityStatusType[$activityindex][$fieldindex]['datatype'] = $datatype;
                  $activityStatusType[$activityindex][$fieldindex]['value'] = $value;
                }
              }
            }
          }

          $fieldindex++;
        }

        if ($printReport) {
          $caseActivities[$activityindex] = CRM_Utils_Array::crmArrayMerge($activityStatusType[$activityindex], $caseActivities[$activityindex]);
          $caseActivities[$activityindex]['sortValues'] = $sortValues;
        }

        if ($includeAll || !$completed) {
          $retval[$activityindex]['completed'] = $completed;
          $retval[$activityindex]['category'] = $category;
          $retval[$activityindex]['sortValues'] = $sortValues;

          // Now sort the fields based on the order in the config file.
          foreach ($regionList as $region) {
            $this->auditConfig->sort($retval[$activityindex][$region], $region);
          }

          $retval[$activityindex]['editurl'] = $activity->getElementsByTagName("EditURL")->item(0)->nodeValue;

          // If there are any fields with ifBlank specified, replace their values.
          // We need to do this as a second pass because if we do it while looping through fields we might not have come across the field we need yet.
          foreach ($regionList as $region) {
            foreach ($retval[$activityindex][$region] as & $v) {
              $vlabel = $v['label'];
              if (trim($v['value']) == '' && !empty($ifBlanks[$region][$vlabel])) {
                if (!empty($ifBlankReplacements[$ifBlanks[$region][$vlabel]])) {
                  $v['value'] = $ifBlankReplacements[$ifBlanks[$region][$vlabel]];
                }
              }
            }
            unset($v);
          }

          $activityindex++;
        }
        else {
          /* This is a little bit inefficient, but the alternative is to do two passes
          because we don't know until we've examined all the field values whether the activity
          is completed, since the field that determines it and its value is configurable,
          so either way isn't ideal. */

          unset($retval[$activityindex]);
          unset($caseActivities[$activityindex]);
        }
      }

      if ($printReport) {
        @uasort($caseActivities, array($this, "compareActivities"));
      }
      else {
        @uasort($retval, array($this, "compareActivities"));
      }
    }

    if ($printReport) {
      return $caseActivities;
    }
    else {
      return $retval;
    }
  }

  /* compareActivities
   *
   * This is intended to be called as a sort callback function, returning whether an activity's date is earlier or later than another's.
   * The type of date to use is specified in the config.
   *
   */

  /**
   * @param $a
   * @param $b
   *
   * @return int
   */
  public function compareActivities($a, $b) {
    // This should work
    foreach ($this->auditConfig->getSortByLabels() as $label) {
      $aval .= empty($a['sortValues']) ? "" : (empty($a['sortValues'][$label]) ? "" : $a['sortValues'][$label]);
      $bval .= empty($b['sortValues']) ? "" : (empty($b['sortValues'][$label]) ? "" : $b['sortValues'][$label]);
    }

    if ($aval < $bval) {
      return - 1;
    }
    elseif ($aval > $bval) {
      return 1;
    }
    else {
      return 0;
    }
  }

  /**
   * @param $xmlString
   * @param $clientID
   * @param $caseID
   * @param bool $printReport
   *
   * @return mixed
   */
  static
  function run($xmlString, $clientID, $caseID, $printReport = FALSE) {
    /*
$fh = fopen('C:/temp/audit2.xml', 'w');
fwrite($fh, $xmlString);
fclose($fh);
*/

    $audit = new CRM_Case_Audit_Audit($xmlString, 'audit.conf.xml');
    $activities = $audit->getActivities($printReport);

    $template = CRM_Core_Smarty::singleton();
    $template->assign_by_ref('activities', $activities);

    if ($printReport) {
      $reportDate = CRM_Utils_Date::customFormat(date('Y-m-d H:i'));
      $template->assign('reportDate', $reportDate);
      $contents = $template->fetch('CRM/Case/Audit/Report.tpl');
    }
    else {
      $contents = $template->fetch('CRM/Case/Audit/Audit.tpl');
    }
    return $contents;
  }
}
