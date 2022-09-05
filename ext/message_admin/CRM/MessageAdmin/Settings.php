<?php

class CRM_MessageAdmin_Settings {

  public static function getAll() {
    $allLangs = \Civi\Api4\OptionValue::get()
      ->addWhere('option_group_id:name', '=', 'languages')
      ->addWhere('is_active', '=', TRUE)
      ->addSelect('name', 'label')
      ->addOrderBy('label')
      ->execute();
    $allLangsIdx = array_combine($allLangs->column('name'), $allLangs->column('label'));

    $usableLangs = \Civi\Api4\MessageTemplate::getActions(0)
      ->addWhere("name", "=", "get")
      ->execute()
      ->single()['params']['language']['options'];

    return [
      'allLanguages' => CRM_Utils_Array::subset($allLangsIdx, $usableLangs),
      'uiLanguages' => CRM_Core_I18n::uiLanguages(),
    ];
  }

}
