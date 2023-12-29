<?php

use CRM_Oembed_ExtensionUtil as E;

class CRM_Oembed_Utils {

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
      'cms' => E::ts('Full page layout (with CMS navigation)'),
    ];
  }

}
