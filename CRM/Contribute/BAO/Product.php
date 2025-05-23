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
class CRM_Contribute_BAO_Product extends CRM_Contribute_DAO_Product implements Civi\Core\HookInterface {

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve(&$params, &$defaults) {
    $premium = self::commonRetrieve(self::class, $params, $defaults);
    if ($premium) {
      $premium->product_name = $premium->name;
    }
    return $premium;
  }

  /**
   * Add a premium product to the database, and return it.
   *
   * @deprecated
   * @param array $params
   *   Update parameters.
   *
   * @return CRM_Contribute_DAO_Product
   */
  public static function create($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * Event fired before modifying a Product.
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if (in_array($event->action, ['create', 'edit'])) {
      // Modify the submitted values for 'image' and 'thumbnail' so that we use
      // local URLs for these images when possible.
      if (isset($event->params['image'])) {
        $event->params['image'] = CRM_Utils_String::simplifyURL($event->params['image'], TRUE);
      }
      if (isset($event->params['thumbnail'])) {
        $event->params['thumbnail'] = CRM_Utils_String::simplifyURL($event->params['thumbnail'], TRUE);
      }
    }
  }

}
