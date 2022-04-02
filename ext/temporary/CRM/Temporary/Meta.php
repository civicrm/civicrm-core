<?php

use CRM_Temporary_ExtensionUtil as E;

class CRM_Temporary_Options {

  public static function getTimestampModes(): array {
    return [
      'auto' => ts('(Auto)'),
      'hybrid' => ts('Hybrid'),
      'ts' => ts('TIMESTAMP Only'),
      'gmt' => ts('DATETIME GMT Only'),
    ];
  }

}
