<?php

class CRM_Msgtplui_Settings {

  public static function getAll() {
    $allLangs = \Civi\Api4\OptionValue::get()
      ->addWhere('option_group_id:name', '=', 'languages')
      ->addWhere('is_active', '=', TRUE)
      ->addSelect('name', 'label')
      ->addOrderBy('label')
      ->execute();
    return [
      'allLanguages' => array_combine($allLangs->column('name'), $allLangs->column('label')),
      'uiLanguages' => CRM_Core_I18n::uiLanguages(),
    ];
  }

}
