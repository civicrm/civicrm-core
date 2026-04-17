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

use Civi\FlexMailer\FlexMailer as FM;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Implements hook_civicrm_alterMailer().
 *
 * Replace the PEAR Mail transport with a Symfony Mailer adapter.
 */
function symfony_mailer_civicrm_alterMailer(&$mailer, string $driver, array $params): void {
  if (in_array($driver, ['CRM_Mailing_BAO_Spool', 'mock'], TRUE)) {
    return;
  }
  try {
    $transport = \Civi\SymfonyMailer\TransportFactory::createFromSettings();
    if ($transport === NULL) {
      return;
    }
    $mailer = new \Civi\SymfonyMailer\SymfonyMailerAdapter($transport, $driver);
  }
  catch (\Exception $e) {
    \Civi::log()->error('symfony_mailer: failed to initialize transport: ' . $e->getMessage());
  }
}

/**
 * Implements hook_civicrm_container().
 *
 * Register the FlexMailer send listener that bypasses Mail_mime.
 */
function symfony_mailer_civicrm_container(ContainerBuilder $container): void {
  $container->register('civi_symfony_mailer_flex_sender', 'Civi\SymfonyMailer\FlexSender')
    ->setPublic(TRUE);
  $container->findDefinition('dispatcher')->addMethodCall(
    'addListenerService',
    [FM::EVENT_SEND, ['civi_symfony_mailer_flex_sender', 'onSend'], -1000]
  );
}
