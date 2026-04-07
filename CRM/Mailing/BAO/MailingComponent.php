<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */


use Civi\Core\Event\PreEvent;
use Civi\Core\Event\PostEvent;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Mailing_BAO_MailingComponent extends CRM_Mailing_DAO_MailingComponent implements Civi\Core\HookInterface {

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return CRM_Core_DAO::setFieldValue('CRM_Mailing_DAO_MailingComponent', $id, 'is_active', $is_active);
  }

  /**
   * Create and Update mailing component.
   *
   * @param array $params
   *
   * @deprecated since 6.8 will be removed around 6.24
   *
   * @return CRM_Mailing_BAO_MailingComponent
   */
  public static function add(array $params) {
    return self::writeRecord($params);
  }

  /**
   * Clear cached options when altering mailing components
   *
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(PostEvent $event): void {
    \Civi::cache('metadata')->clear();
  }

  /**
   * Event fired when creating a contribution
   *
   * @param \Civi\Core\Event\PreEvent $event
   *
   * @throws \CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(PreEvent $event): void {
    if ($event->action === 'create') {
      if (empty($event->params['body_text'])) {
        $event->params['body_text'] = CRM_Utils_String::htmlToText($event->params['body_html'] ?? '');
      }
      if (!empty($event->params['is_default'])) {
        CRM_Core_DAO::executeQuery('UPDATE civicrm_mailing_component SET is_default = 0 WHERE component_type = %1', [
          1 => [$event->params['component_type'], 'String'],
        ]);
      }
    }
    elseif (!empty($event->params['is_default'])) {
      if (empty($event->params['component_type'])) {
        $event->params['component_type'] = CRM_Core_DAO::singleValueQuery('SELECT component_type FROM civicrm_mailing_component WHERE id = %1', [1 => [$event->params['id'], 'Positive']]);
      }
      CRM_Core_DAO::executeQuery('UPDATE civicrm_mailing_component SET is_default = 0 WHERE component_type = %1 AND id <> %2', [
        1 => [$event->params['component_type'], 'String'],
        2 => [$event->params['id'], 'Positive'],
      ]);
    }

  }

}
