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
namespace Civi\FlexMailer\Event;

/**
 * Class BaseEvent
 * @package Civi\FlexMailer\Event
 */
class BaseEvent extends \Symfony\Component\EventDispatcher\Event {
  /**
   * @var array
   *   An array which must define options:
   *     - mailing: \CRM_Mailing_BAO_Mailing
   *     - job: \CRM_Mailing_BAO_MailingJob
   *     - attachments: array
   */
  public $context;

  /**
   * BaseEvent constructor.
   * @param array $context
   */
  public function __construct(array $context) {
    $this->context = $context;
  }

  /**
   * @return \CRM_Mailing_BAO_Mailing
   */
  public function getMailing() {
    return $this->context['mailing'];
  }

  /**
   * @return \CRM_Mailing_BAO_MailingJob
   */
  public function getJob() {
    return $this->context['job'];
  }

  /**
   * @return array|NULL
   */
  public function getAttachments() {
    return $this->context['attachments'];
  }

}
