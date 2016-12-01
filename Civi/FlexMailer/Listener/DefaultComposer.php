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

use Civi\FlexMailer\Event\ComposeBatchEvent;
use Civi\FlexMailer\Event\RunEvent;
use Civi\FlexMailer\TrackableURL;
use Civi\Token\TokenProcessor;
use Civi\Token\TokenRow;

class DefaultComposer extends BaseListener {

  public function onRun(RunEvent $e) {
    // FIXME: This probably doesn't belong here...
    if (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY) {
      \CRM_Core_Smarty::registerStringResource();
    }
  }

  /**
   * Given a mailing and a batch of recipients, prepare
   * the individual messages (headers and body) for each.
   *
   * @param \Civi\FlexMailer\Event\ComposeBatchEvent $e
   */
  public function onComposeBatch(ComposeBatchEvent $e) {
    if (!$this->isActive()) {
      return;
    }

    $e->stopPropagation();

    $mailing = $e->getMailing();

    if (property_exists($mailing, 'language') && $mailing->language && $mailing->language != 'en_US') {
      $swapLang = \CRM_Utils_AutoClean::swap('global://dbLocale?getter', 'call://i18n/setLocale', $mailing->language);
    }

    $tp = $this->createTokenProcessor($e)->evaluate();
    foreach ($tp->getRows() as $row) {
      /** @var TokenRow $row */
      /** @var FlexMailerTask $task */
      $task = $row->context['flexMailerTask'];

      // Ugh, getVerpAndUrlsAndHeaders() is immensely silly.
      list($verp) = $mailing->getVerpAndUrlsAndHeaders(
        $e->getJob()->id, $task->getEventQueueId(), $task->getHash(), $task->getAddress());

      $mailParams = array();

      // Email headers
      $mailParams['Return-Path'] = $verp['bounce'];
      $mailParams['From'] = "\"{$mailing->from_name}\" <{$mailing->from_email}>";
      $mailParams['List-Unsubscribe'] = "<mailto:{$verp['unsubscribe']}>";
      \CRM_Mailing_BAO_Mailing::addMessageIdHeader($mailParams, 'm', $e->getJob()->id, $task->getEventQueueId(), $task->getHash());
      $mailParams['Subject'] = $row->render('subject');
      //if ($isForward) {$mailParams['Subject'] = "[Fwd:{$this->subject}]";}
      $mailParams['Precedence'] = 'bulk';
      $mailParams['X-CiviMail-Bounce'] = $verp['bounce'];
      $mailParams['Reply-To'] = $verp['reply'];
      if ($mailing->replyto_email && ($mailParams['From'] != $mailing->replyto_email)) {
        $mailParams['Reply-To'] = $mailing->replyto_email;
      }

      // Oddballs
      $mailParams['text'] = $row->render('body_text');
      $mailParams['html'] = $row->render('body_html');
      $mailParams['attachments'] = $e->getAttachments();
      $mailParams['toName'] = $row->render('toName');
      $mailParams['toEmail'] = $task->getAddress();
      $mailParams['job_id'] = $e->getJob()->id;

      $task->setMailParams($mailParams);
    }
  }

  /**
   * Instantiate a TokenProcessor, filling in the appropriate templates
   * ("subject", "body_text", "body_html") as well the recipient metadata
   * ("contactId", "mailingJobId", etc).
   *
   * @param \Civi\FlexMailer\Event\ComposeBatchEvent $e
   * @return TokenProcessor
   */
  protected function createTokenProcessor(ComposeBatchEvent $e) {
    $templates = $e->getMailing()->getTemplates();

    // This needs a better place to go.
    if ($e->getMailing()->url_tracking) {
      if (!empty($templates['html'])) {
        $templates['html'] = TrackableURL::scanAndReplace($templates['html'], $e->getMailing()->id, '{action.eventQueueId}', TRUE);
      }
      if (!empty($templates['text'])) {
        $templates['text'] = TrackableURL::scanAndReplace($templates['text'], $e->getMailing()->id, '{action.eventQueueId}', FALSE);
      }
    }

    $tp = new TokenProcessor(\Civi::service('dispatcher'), array(
      'controller' => '\Civi\FlexMailer\Listener\DefaultComposer',
      // FIXME: Use template_type, template_options
      'smarty' => defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY ? TRUE : FALSE,
      'mailingId' => $e->getMailing()->id,
    ));
    $tp->addMessage('toName', '{contact.display_name}', 'text/plain');
    $tp->addMessage('subject', $templates['subject'], 'text/plain');
    $tp->addMessage('body_text', isset($templates['text']) ? $templates['text'] : '', 'text/plain');
    $tp->addMessage('body_html', isset($templates['html']) ? $templates['html'] : '', 'text/html');

    foreach ($e->getTasks() as $key => $task) {
      /** @var FlexMailerTask $task */

      $tp->addRow()->context(array(
        'contactId' => $task->getContactId(),
        'mailingJobId' => $e->getJob()->id,
        'mailingActionTarget' => array(
          'id' => $task->getEventQueueId(),
          'hash' => $task->getHash(),
          'email' => $task->getAddress(),
        ),
        'flexMailerTask' => $task,
      ));
    }
    return $tp;
  }

}
