<?php

/**
 * Class CRM_Case_Audit_AuditConfig
 */
class CRM_Case_Audit_AuditConfig {
  private $filename;
  private $completionLabel;
  private $completionValue;
  private $sortByLabels;
  private $regionFieldList;
  private $includeRules;
  private $sortRegion;
  private $ifBlanks;

  /**
   * @param string $filename
   */
  public function __construct($filename) {
    $this->filename = $filename;

    // set some defaults
    $this->completionLabel = "Status";
    $this->completionValue = "Completed";
    $this->sortByLabels = ["Actual Date", "Due Date"];
    $this->ifBlanks = [];

    $this->loadConfig();
  }

  /**
   * @return string
   */
  public function getCompletionValue() {
    return $this->completionValue;
  }

  /**
   * @return string
   */
  public function getCompletionLabel() {
    return $this->completionLabel;
  }

  /**
   * @return array
   */
  public function getSortByLabels() {
    return $this->sortByLabels;
  }

  /**
   * @return array
   */
  public function getIfBlanks() {
    return $this->ifBlanks;
  }

  public function loadConfig() {
    $this->regionFieldList = [];
    $this->includeRules = [];

    $doc = new DOMDocument();
    $xmlString = file_get_contents(dirname(__FILE__) . '/' . $this->filename);
    $load = $doc->loadXML($xmlString);
    if ($load) {
      $regions = $doc->getElementsByTagName("region");
      foreach ($regions as $region) {
        $regionName = $region->getAttribute("name");
        $this->regionFieldList[$regionName] = [];

        // Inclusion/exclusion settings
        $includeRule = $region->getAttribute("includeRule");
        if (empty($includeRule)) {
          $includeRule = 'include';
        }
        $this->includeRules[$regionName] = ['rule' => $includeRule];
        if ($includeRule == 'exclude') {
          $altRegion = $region->getAttribute("exclusionCorrespondingRegion");
          $this->includeRules[$regionName]['altRegion'] = $altRegion;
        }

        // Time component display settings
        $includeTime = $region->getAttribute("includeTime");
        if (empty($includeTime)) {
          $includeTime = 'false';
        }
        $this->includeRules[$regionName]['includeTime'] = $includeTime;

        $fieldCount = 0;
        $fields = $region->getElementsByTagName("field");
        foreach ($fields as $field) {
          /* Storing them this way, which is backwards to how you might normally
          have arrays with a numeric key and a text value, ends up making things better
          in the other functions, in particular the sorting and also inRegion should end
          up being more efficient (searching for a key instead of a value). */

          $this->regionFieldList[$regionName][$field->nodeValue] = $fieldCount;

          // Field-level overrides of time component display settings
          $includeTime = $field->getAttribute("includeTime");
          if (!empty($includeTime)) {
            $this->regionFieldList[$regionName][$field->nodeValue]['includeTime'] = $includeTime;
          }

          // ifBlank attribute
          $ifBlank = $field->getAttribute("ifBlank");
          if (!empty($ifBlank)) {
            $this->ifBlanks[$regionName][$field->nodeValue] = $ifBlank;
          }

          $fieldCount++;
        }
      }

      $completionStatus = $doc->getElementsByTagName("completionStatus");
      if (!empty($completionStatus)) {
        $label_elements = $completionStatus->item(0)->getElementsByTagName("label");
        $this->completionLabel = $label_elements->item(0)->nodeValue;

        $value_elements = $completionStatus->item(0)->getElementsByTagName("value");
        $this->completionValue = $value_elements->item(0)->nodeValue;
      }

      $sortElement = $doc->getElementsByTagName("sortByLabels");
      if (!empty($sortElement)) {
        $this->sortByLabels = [];
        $label_elements = $sortElement->item(0)->getElementsByTagName("label");
        foreach ($label_elements as $ele) {
          $this->sortByLabels[] = $ele->nodeValue;
        }
      }
    }
  }

  /**
   * Check if label $n is explicitly listed in region $r in the config.
   *
   * @param $n
   * @param $r
   *
   * @return bool
   */
  public function inRegion($n, $r) {
    if (empty($this->regionFieldList[$r])) {
      return FALSE;
    }
    else {
      return array_key_exists($n, $this->regionFieldList[$r]);
    }
  }

  /**
   * Should field $n be included in region $r, taking into account exclusion rules.
   *
   * @param $n
   * @param $r
   *
   * @return bool
   */
  public function includeInRegion($n, $r) {
    $add_it = FALSE;
    $rules = $this->includeRules[$r];
    if ($rules['rule'] == 'exclude') {
      if (!$this->inRegion($n, $r) && !$this->inRegion($n, $rules['altRegion'])) {
        $add_it = TRUE;
      }
    }
    elseif ($this->inRegion($n, $r)) {
      $add_it = TRUE;
    }
    return $add_it;
  }

  /**
   * Should the time component of field $n in region $r be displayed?
   *
   * @param $n
   * @param $r
   *
   * @return bool
   */
  public function includeTime($n, $r) {
    $retval = FALSE;
    if (empty($this->regionFieldList[$r][$n]['includeTime'])) {
      // No field-level override, so look at the region's settings
      if (!empty($this->includeRules[$r]['includeTime'])) {
        $retval = $this->includeRules[$r]['includeTime'];
      }
    }
    else {
      $retval = $this->regionFieldList[$r][$n]['includeTime'];
    }

    // There's a mix of strings and boolean, so convert any strings.
    if ($retval == 'false') {
      $retval = FALSE;
    }
    elseif ($retval == 'true') {
      $retval = TRUE;
    }

    return $retval;
  }

  /**
   * Return a list of all the regions in the config file.
   *
   * @return array
   */
  public function getRegions() {
    return array_keys($this->regionFieldList);
  }

  /**
   * Sort a group of fields for a given region according to the order in the config.
   * The array to be sorted should have elements that have a member with a key of 'label', and the value should be the field label.
   *
   * @param $f
   * @param $r
   */
  public function sort(&$f, $r) {
    // For exclusion-type regions, there's nothing to do, because we won't have been given any ordering.
    if ($this->includeRules[$r]['rule'] == 'exclude') {
      return;
    }

    $this->sortRegion = $r;
    uasort($f, array(&$this, "compareFields"));
  }

  /**
   * This is intended to be called as a sort callback function, returning whether a field in a region comes before or after another one.
   * See also PHP's usort().
   *
   * @param $a
   * @param $b
   *
   * @return int
   */
  public function compareFields($a, $b) {
    if (empty($this->regionFieldList[$this->sortRegion][$a['label']])) {
      $x = 0;
    }
    else {
      $x = $this->regionFieldList[$this->sortRegion][$a['label']];
    }

    if (empty($this->regionFieldList[$this->sortRegion][$b['label']])) {
      $y = 0;
    }
    else {
      $y = $this->regionFieldList[$this->sortRegion][$b['label']];
    }

    return $x - $y;
  }

}
