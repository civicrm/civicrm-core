<?php

class CRM_Upgrade_Snapshot_V4p2_Case_XMLProcessor {
  /**
   * @param string $caseType
   * @return string
   * @see CRM_Case_XMLProcessor::mungeCaseType
   */
  public static function mungeCaseType($caseType) {
    $caseType = str_replace('_', ' ', $caseType);
    $caseType = self::_munge(ucwords($caseType), '', 0);
    return $caseType;
  }

  static function _munge($name, $char = '_', $len = 63) {
    $name = preg_replace('/[^a-zA-Z0-9]+/', $char, trim($name));
    if ($len) {
      // lets keep variable names short
      return substr($name, 0, $len);
    }
    else {
      return $name;
    }
  }

}