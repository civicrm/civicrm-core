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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Mailing_BAO_MailingComponent extends CRM_Mailing_DAO_MailingComponent {

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
   *   (reference ) an assoc array of name/value pairs.
   * @param array $ids
   *   (deprecated) the array that holds all the db ids.
   *
   * @return CRM_Mailing_BAO_MailingComponent
   */
  public static function add(&$params, $ids = []) {
    $id = $params['id'] ?? $ids['id'] ?? NULL;
    $component = new CRM_Mailing_BAO_MailingComponent();
    if ($id) {
      $component->id = $id;
      $component->find(TRUE);
    }

    $component->copyValues($params);
    if (empty($id) && empty($params['body_text'])) {
      $component->body_text = CRM_Utils_String::htmlToText($params['body_html'] ?? '');
    }

    if ($component->is_default) {
      if (!empty($id)) {
        $sql = 'UPDATE civicrm_mailing_component SET is_default = 0 WHERE component_type = %1 AND id <> %2';
        $sqlParams = [
          1 => [$component->component_type, 'String'],
          2 => [$id, 'Positive'],
        ];
      }
      else {
        $sql = 'UPDATE civicrm_mailing_component SET is_default = 0 WHERE component_type = %1';
        $sqlParams = [
          1 => [$component->component_type, 'String'],
        ];
      }
      CRM_Core_DAO::executeQuery($sql, $sqlParams);
    }

    $component->save();
    return $component;
  }

}
