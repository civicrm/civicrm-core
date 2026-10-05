<?php

class CRM_MessageAdmin_Settings {

  public static function getAll() {
    // sending test emails using EmailMessage.create requires
    // a) the postbox extension to be enabled
    // b) the user has administer CiviCRM permissions
    // note: status = installed actually means enabled :/
    $sendTestEnabled = \CRM_Core_Permission::check('administer CiviCRM')
      && (bool) \Civi\Api4\Extension::get(FALSE)
        ->addWhere('key', '=', 'postbox')
        ->addWhere('status:name', '=', 'installed')
        ->execute()
        ->first();

    return self::getTaskSettings() + [
      'sendTestEnabled' => $sendTestEnabled,
    ];
  }

  /**
   * Settings for the SearchKit tasks.
   *
   * Narrower than getAll(): this module is declared by a task rather than a route, so its factory
   * runs for every visitor who loads Angular, not just someone on the editor.
   *
   * @return array
   */
  public static function getTaskSettings(): array {
    return [
      'allLanguages' => self::getAvailableLanguages(),
      'uiLanguages' => CRM_Core_I18n::uiLanguages(),
    ];
  }

  /**
   * Languages a message template can be translated into, keyed by locale name.
   *
   * A site with one or none of these cannot meaningfully hold translations.
   *
   * @return array
   */
  public static function getAvailableLanguages(): array {
    $allLangs = \Civi\Api4\OptionValue::get(FALSE)
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

    return CRM_Utils_Array::subset($allLangsIdx, $usableLangs);
  }

}
