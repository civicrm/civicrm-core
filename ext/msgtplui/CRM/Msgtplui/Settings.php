<?php

class CRM_Msgtplui_Settings {
  public static function getAll() {
    return [
      'uiLanguages' => CRM_Core_I18n::uiLanguages(),
    ];
  }
}
