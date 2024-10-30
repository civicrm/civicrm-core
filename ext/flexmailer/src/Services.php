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
namespace Civi\FlexMailer;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Civi\FlexMailer\FlexMailer as FM;

/**
 * Class Services
 * @package Civi\FlexMailer
 *
 * Manage the setup of any services used by FlexMailer.
 */
class Services {

  public static function registerServices(ContainerBuilder $container) {
    $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));

    $apiOverrides = $container->setDefinition('civi_flexmailer_api_overrides', new Definition('Civi\API\Provider\ProviderInterface'))->setPublic(TRUE);
    self::applyStaticFactory($apiOverrides, __CLASS__, 'createApiOverrides');

    foreach (self::getListenerSpecs() as $listenerSpec) {
      $container->findDefinition('dispatcher')->addMethodCall('addListenerService', $listenerSpec);
    }

    $container->findDefinition('civi_api_kernel')->addMethodCall('registerApiProvider', [new Reference('civi_flexmailer_api_overrides')]);
  }

  /**
   * Get a list of listeners required for FlexMailer.
   *
   * This is a standalone, private function because we're experimenting
   * with how exactly to handle the registration -- e.g. via
   * `registerServices()` or via `registerListeners()`.
   *
   * @return array
   *   Arguments to pass to addListenerService($eventName, $callbackSvc, $priority).
   */
  protected static function getListenerSpecs() {
    $listenerSpecs = [];

    $listenerSpecs[] = [Validator::EVENT_CHECK_SENDABLE, ['civi_flexmailer_abdicator', 'onCheckSendable'], FM::WEIGHT_START];
    $listenerSpecs[] = [Validator::EVENT_CHECK_SENDABLE, ['civi_flexmailer_required_fields', 'onCheckSendable'], FM::WEIGHT_MAIN];
    $listenerSpecs[] = [Validator::EVENT_CHECK_SENDABLE, ['civi_flexmailer_required_tokens', 'onCheckSendable'], FM::WEIGHT_MAIN];

    $listenerSpecs[] = [FM::EVENT_RUN, ['civi_flexmailer_default_composer', 'onRun'], FM::WEIGHT_MAIN];
    $listenerSpecs[] = [FM::EVENT_RUN, ['civi_flexmailer_abdicator', 'onRun'], FM::WEIGHT_END];

    $listenerSpecs[] = [FM::EVENT_WALK, ['civi_flexmailer_default_batcher', 'onWalk'], FM::WEIGHT_END];

    $listenerSpecs[] = [FM::EVENT_COMPOSE, ['civi_flexmailer_basic_headers', 'onCompose'], FM::WEIGHT_PREPARE];
    $listenerSpecs[] = [FM::EVENT_COMPOSE, ['civi_flexmailer_to_header', 'onCompose'], FM::WEIGHT_PREPARE];
    $listenerSpecs[] = [FM::EVENT_COMPOSE, ['civi_flexmailer_bounce_tracker', 'onCompose'], FM::WEIGHT_PREPARE];
    $listenerSpecs[] = [FM::EVENT_COMPOSE, ['civi_flexmailer_default_composer', 'onCompose'], FM::WEIGHT_MAIN - 100];
    $listenerSpecs[] = [FM::EVENT_COMPOSE, ['civi_flexmailer_attachments', 'onCompose'], FM::WEIGHT_ALTER];
    $listenerSpecs[] = [FM::EVENT_COMPOSE, ['civi_flexmailer_open_tracker', 'onCompose'], FM::WEIGHT_ALTER];
    $listenerSpecs[] = [FM::EVENT_COMPOSE, ['civi_flexmailer_test_prefix', 'onCompose'], FM::WEIGHT_ALTER];
    $listenerSpecs[] = [FM::EVENT_COMPOSE, ['civi_flexmailer_hooks', 'onCompose'], FM::WEIGHT_ALTER - 100];

    $listenerSpecs[] = [FM::EVENT_SEND, ['civi_flexmailer_default_sender', 'onSend'], FM::WEIGHT_END];

    return $listenerSpecs;
  }

  /**
   * Tap into the API kernel and override some of the core APIs.
   *
   * @return \Civi\API\Provider\AdhocProvider
   */
  public static function createApiOverrides() {
    $provider = new \Civi\API\Provider\AdhocProvider(3, 'Mailing');
    // FIXME: stay in sync with upstream perms
    $provider->addAction('preview', 'access CiviMail', '\Civi\FlexMailer\API\MailingPreview::preview');
    return $provider;
  }

  /**
   * Adapter for using factory methods in old+new versions of Symfony.
   *
   * @param \Symfony\Component\DependencyInjection\Definition $def
   * @param string $factoryClass
   * @param string $factoryMethod
   * @return \Symfony\Component\DependencyInjection\Definition
   * @deprecated
   */
  protected static function applyStaticFactory($def, $factoryClass, $factoryMethod) {
    if (method_exists($def, 'setFactory')) {
      $def->setFactory([$factoryClass, $factoryMethod]);
    }
    else {
      $def->setFactoryClass($factoryClass)->setFactoryMethod($factoryMethod);
    }
    return $def;
  }

}
