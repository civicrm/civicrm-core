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

    $container->setDefinition('civi_flexmailer_required_fields', new Definition('Civi\FlexMailer\Listener\RequiredFields', [
      [
        'subject',
        'name',
        'from_name',
        'from_email',
        '(body_html|body_text)',
      ],
    ]))->setPublic(TRUE);
    $container->setDefinition('civi_flexmailer_required_tokens', new Definition('Civi\FlexMailer\Listener\RequiredTokens', [
      ['traditional'],
      [
        'domain.address' => ts("Domain address - displays your organization's postal address."),
        'action.optOutUrl or action.unsubscribeUrl' => [
          'action.optOut' => ts("'Opt out via email' - displays an email address for recipients to opt out of receiving emails from your organization."),
          'action.optOutUrl' => ts("'Opt out via web page' - creates a link for recipients to click if they want to opt out of receiving emails from your organization. Alternatively, you can include the 'Opt out via email' token."),
          'action.unsubscribe' => ts("'Unsubscribe via email' - displays an email address for recipients to unsubscribe from the specific mailing list used to send this message."),
          'action.unsubscribeUrl' => ts("'Unsubscribe via web page' - creates a link for recipients to unsubscribe from the specific mailing list used to send this message. Alternatively, you can include the 'Unsubscribe via email' token or one of the Opt-out tokens."),
        ],
      ],
    ]))->setPublic(TRUE);

    $container->setDefinition('civi_flexmailer_abdicator', new Definition('Civi\FlexMailer\Listener\Abdicator'))->setPublic(TRUE);
    $container->setDefinition('civi_flexmailer_default_batcher', new Definition('Civi\FlexMailer\Listener\DefaultBatcher'))->setPublic(TRUE);
    $container->setDefinition('civi_flexmailer_default_composer', new Definition('Civi\FlexMailer\Listener\DefaultComposer'))->setPublic(TRUE);
    $container->setDefinition('civi_flexmailer_open_tracker', new Definition('Civi\FlexMailer\Listener\OpenTracker'))->setPublic(TRUE);
    $container->setDefinition('civi_flexmailer_basic_headers', new Definition('Civi\FlexMailer\Listener\BasicHeaders'))->setPublic(TRUE);
    $container->setDefinition('civi_flexmailer_to_header', new Definition('Civi\FlexMailer\Listener\ToHeader'))->setPublic(TRUE);
    $container->setDefinition('civi_flexmailer_attachments', new Definition('Civi\FlexMailer\Listener\Attachments'))->setPublic(TRUE);
    $container->setDefinition('civi_flexmailer_bounce_tracker', new Definition('Civi\FlexMailer\Listener\BounceTracker'))->setPublic(TRUE);
    $container->setDefinition('civi_flexmailer_default_sender', new Definition('Civi\FlexMailer\Listener\DefaultSender'))->setPublic(TRUE);
    $container->setDefinition('civi_flexmailer_hooks', new Definition('Civi\FlexMailer\Listener\HookAdapter'))->setPublic(TRUE);
    $container->setDefinition('civi_flexmailer_test_prefix', new Definition('Civi\FlexMailer\Listener\TestPrefix'))->setPublic(TRUE);

    $container->setDefinition('civi_flexmailer_html_click_tracker', new Definition('Civi\FlexMailer\ClickTracker\HtmlClickTracker'))->setPublic(TRUE);
    $container->setDefinition('civi_flexmailer_text_click_tracker', new Definition('Civi\FlexMailer\ClickTracker\TextClickTracker'))->setPublic(TRUE);

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
