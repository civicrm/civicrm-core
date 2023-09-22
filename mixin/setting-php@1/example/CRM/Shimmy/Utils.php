<?php

use CRM_Shimmy_ExtensionUtil as E;

class CRM_Shimmy_Utils {

  /**
   * @return array
   */
  public static function getExampleOptions(): array {
    return [
      'first' => E::ts('First example'),
      'second' => E::ts('Second example'),
    ];
  }

}
