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
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Hook_Drupal8 extends CRM_Utils_Hook_DrupalBase {

  /**
   * {@inheritdoc}
   */
  protected function getDrupalModules() {
    if (class_exists('\Drupal') && \Drupal::hasContainer()) {
      return array_keys(\Drupal::moduleHandler()->getModuleList());
    }
  }

}
