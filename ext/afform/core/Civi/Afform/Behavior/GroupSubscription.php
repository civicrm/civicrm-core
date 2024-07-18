<?php
namespace Civi\Afform\Behavior;

use Civi\Afform\AbstractBehavior;
use Civi\Afform\Event\AfformPrefillEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CRM_Afform_ExtensionUtil as E;

/**
 * @service
 * @internal
 */
class GroupSubscription extends AbstractBehavior implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.afform.prefill' => ['onAfformPrefill', 99],
    ];
  }

  public static function getEntities():array {
    return ['GroupSubscription'];
  }

  public static function getTitle():string {
    return E::ts('Configuration');
  }

  public static function getDescription():string {
    return E::ts('Configue subscription behavior.');
  }

  public static function getDefaultMode(): string {
    return 'normal';
  }

  public static function getModes(string $contactType):array {
    $modes = [
      [
        'name' => 'normal',
        'label' => E::ts('Opts in and out'),
        'description' => E::ts('Double optin for sign up'),
      ],
      [
        'name' => 'opt-in',
        'label' => E::ts('Only allows opting in'),
        'description' => E::ts('Double optin for sign up'),
      ],
      [
        'name' => 'auto-add',
        'label' => E::ts('Adds contact on submission'),
      ],
      [
        'name' => 'auto-remove',
        'label' => E::ts('Removes contact on submission'),
      ],

    ];
    return $modes;
  }

  public static function onAfformPrefill(AfformPrefillEvent $event): void {
  }

}
