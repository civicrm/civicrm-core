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
namespace Civi\FlexMailer;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;

/**
 * Class Services
 * @package Civi\FlexMailer
 *
 * Manage the setup of any services used by FlexMailer.
 */
class Services {

  public static function registerServices(ContainerBuilder $container) {
    if (version_compare(\CRM_Utils_System::version(), '4.7.0', '>=')) {
      $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
    }
    $container->setParameter('civi_flexmailer_callback', '\Civi\FlexMailer\FlexMailer::createAndRun');
    $container->setDefinition('civi_flexmailer_abdicator', new Definition('Civi\FlexMailer\Listener\Abdicator'));
    $container->setDefinition('civi_flexmailer_default_batcher', new Definition('Civi\FlexMailer\Listener\DefaultBatcher'));
    $container->setDefinition('civi_flexmailer_default_composer', new Definition('Civi\FlexMailer\Listener\DefaultComposer'));
    $container->setDefinition('civi_flexmailer_open_tracker', new Definition('Civi\FlexMailer\Listener\OpenTracker'));
    $container->setDefinition('civi_flexmailer_default_sender', new Definition('Civi\FlexMailer\Listener\DefaultSender'));
    $container->setDefinition('civi_flexmailer_hooks', new Definition('Civi\FlexMailer\Listener\HookAdapter'));
  }

  public static function registerListeners(ContainerAwareEventDispatcher $dispatcher) {
    $terminalPriority = -100;
    $dispatcher->addListenerService(FlexMailer::EVENT_RUN, array('civi_flexmailer_abdicator', 'onRun'), $terminalPriority);
    $dispatcher->addListenerService(FlexMailer::EVENT_WALK, array('civi_flexmailer_default_batcher', 'onWalkBatches'), $terminalPriority);
    $dispatcher->addListenerService(FlexMailer::EVENT_RUN, array('civi_flexmailer_default_composer', 'onRun'), 0);
    $dispatcher->addListenerService(FlexMailer::EVENT_COMPOSE, array('civi_flexmailer_default_composer', 'onComposeBatch'), $terminalPriority);
    $dispatcher->addListenerService(FlexMailer::EVENT_ALTER, array('civi_flexmailer_open_tracker', 'onAlterBatch'), -20);
    $dispatcher->addListenerService(FlexMailer::EVENT_ALTER, array('civi_flexmailer_hooks', 'onAlterBatch'), -30);
    $dispatcher->addListenerService(FlexMailer::EVENT_SEND, array('civi_flexmailer_default_sender', 'onSendBatch'), $terminalPriority);
  }

}
