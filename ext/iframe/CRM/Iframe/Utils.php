<?php

use CRM_Iframe_ExtensionUtil as E;

class CRM_Iframe_Utils {

  public static function getAllowOptions(): array {
    return [
      'public' => E::ts('All public pages'),
      'ajax' => E::ts('All AJAX routes'),
    ];
  }

  public static function getLayoutOptions(): array {
    return [
      'auto' => E::ts('Automatic'),
      'raw' => E::ts('Raw page layout (no headers)'),
      'basic' => E::ts('Basic page layout (with headers, without navigation)'),
      'cms' => E::ts('Full page layout (with CMS navigation)'),
    ];
  }

}
