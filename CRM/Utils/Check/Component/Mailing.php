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
class CRM_Utils_Check_Component_Mailing extends CRM_Utils_Check_Component {

  /**
   * @return CRM_Utils_Check_Message[]
   */
  public function checkUnsubscribeMethods() {
    if (!\CRM_Core_Component::isEnabled('CiviMail')) {
      return [];
    }

    $methods = Civi::settings()->get('civimail_unsubscribe_methods') ?: [];
    if (in_array('oneclick', $methods)) {
      return [];
    }

    // OK, all guards passed. Show message.
    $message = new CRM_Utils_Check_Message(
      __FUNCTION__,
      '<p>' . ts('Beginning in 2024, some web-mail services (Google and Yahoo) will require that large mailing-lists support another unsubscribe method: "HTTP One-Click" (RFC 8058). Please review the documentation and update the settings.') . '</p>',
      ts('CiviMail: Enable One-Click Unsubscribe'),
      \Psr\Log\LogLevel::NOTICE,
      'fa-server'
    );
    $message->addAction(ts('Learn more'), FALSE, 'href', ['url' => 'https://civicrm.org/redirect/unsubscribe-one-click'], 'fa-info-circle');
    $message->addAction(ts('Update settings'), FALSE, 'href', ['path' => 'civicrm/admin/mail', 'query' => 'reset=1'], 'fa-wrench');

    return [$message];
  }

}
