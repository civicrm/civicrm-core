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

namespace Civi\AfformAdmin;

use Civi\Core\Service\AutoSubscriber;
use CRM_Afform_ExtensionUtil as E;

/**
 * @package Civi\AfformAdmin
 */
class AfformAdminInjector extends AutoSubscriber {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_alterAngular' => 'preprocess',
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::alterAngular()
   */
  public static function preprocess($e) {
    $changeSet = \Civi\Angular\ChangeSet::create('afformAdmin')
      ->alterHtml(';\\.aff\\.html$;', function($doc, $path) {
        try {
          $moduleName = basename($path, '.aff.html');
          // If the user has "administer CiviCRM", inject edit link
          if (\CRM_Core_Permission::check('administer CiviCRM')) {
            $url = \CRM_Utils_System::url('civicrm/admin/afform', NULL, FALSE, '/edit/' . $moduleName, TRUE);
            // Append link to afform directive element (using loop but there should be only one)
            foreach (pq('af-form[ctrl]', $doc) as $afForm) {
              pq($afForm)->append('<a href="' . $url . '" target="_blank" class="af-admin-edit-form-link"><i class="crm-i fa-gear"></i> ' . E::ts('Edit Form') . '</a>');
            }
          }
        }
        catch (\Exception $e) {
        }
      });
    $e->angular->add($changeSet);
  }

}
