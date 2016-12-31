<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */
namespace Civi\FlexMailer\Listener;

use Civi\FlexMailer\Event\RunEvent;

class Abdicator {

  /**
   * Abdicate; defer to the old system.
   *
   * The FlexMailer is in incubation -- it's a heavily reorganized version
   * of the old MailingJob::deliver*() functions. It hasn't been tested as
   * thoroughly and may not have perfect parity.
   *
   * During incubation, we want to mostly step-aside -- instead,
   * simply continue using the old system.
   *
   * @param \Civi\FlexMailer\Event\RunEvent $e
   */
  public function onRun(RunEvent $e) {
    // Hidden setting: "experimentalFlexMailerEngine" (bool)
    // If TRUE, we will always use FlexMailer's events.
    // Otherwise, we'll generally abdicate.
    if (\CRM_Core_BAO_Setting::getItem('Mailing Preferences', 'experimentalFlexMailerEngine')) {
      return; // OK, we'll continue running.
    }

    // Use FlexMailer for new-style email blasts (with custom `template_type`).
    $mailing = $e->getMailing();
    if ($mailing->template_type && $mailing->template_type !== 'traditional' && !$mailing->sms_provider_id) {
      return; // OK, we'll continue running.
    }

    // Nope, we'll abdicate.
    $e->stopPropagation();
    $isDelivered = $e->getJob()->deliver(
      $e->context['deprecatedMessageMailer'],
      $e->context['deprecatedTestParams']
    );
    $e->setCompleted($isDelivered);
  }

}
