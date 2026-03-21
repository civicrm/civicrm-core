<?php
namespace Civi;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoService;
use CRM_Core_Session;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use CRM_Contribute_ExtensionUtil as E;

/**
 * @service civi.checkout
 */
class Checkout extends AutoService implements EventSubscriberInterface {

  /**
   * @var \Civi\Checkout\CheckoutOptionInterface[]
   *
   * Local cache of options
   */
  protected ?array $options = NULL;

  public static function getSubscribedEvents(): array {
    return [
      // listen to our own event to add the default pay later option
      // we add this early so an extension can replace with something
      // more bespoke if desired
      'civi.checkout.options' => ['addPayLaterOption', 100],
      // open access to Contribution.continueCheckout
      // (authorization uses JWT)
      'civi.api.authorize' => [
        ['authorizeContinueCheckout', 100],
      ],
    ];
  }

  protected function isEnabled(): bool {
    return !!\Civi::settings()->get('contribute_enable_afform_contributions');
  }

  /**
   * Gather available CheckoutOptions
   *
   * Note: currently this is a totally global list. In the future we
   * may want to distinguish options based on frontend (e.g. afform / quickform / ?)
   *
   * A site builder may specify a further subset of available options on
   * a specific options on a specific afform / contribution page etc
   *
   * @return \Civi\Checkout\CheckoutOptionInterface[]
   */
  public function getOptions(): array {
    if (!$this->isEnabled()) {
      // dont even fire the hook
      return [];
    }
    if (!is_array($this->options)) {
      $e = GenericHookEvent::create(['options' => []]);
      \Civi::dispatcher()->dispatch('civi.checkout.options', $e);
      $this->options = $e->options;
    }
    return $this->options;
  }

  public function getOption(string $name): Checkout\CheckoutOptionInterface {
    $option = $this->getOptions()[$name] ?? NULL;
    if (!$option) {
      throw new \CRM_Core_Exception("No CheckoutOption found with name: {$name}");
    }
    return $option;
  }

  public function isTestMode(): bool {
    $session = CRM_Core_Session::singleton();
    return !!\CRM_Utils_Request::retrieve('testMode', 'Boolean', $session);
  }

  public function addPayLaterOption(GenericHookEvent $e) {
    $e->options['pay_later'] = new Checkout\PayLater();
  }

  /**
   * Contribution.continueCheckout is open to anyone with make online contributions
   * - authentication of the specific checkout is done using JWT param
   */
  public function authorizeContinueCheckout(\Civi\API\Event\AuthorizeEvent $event) {
    if (!$this->isEnabled()) {
      // do nothing
      return;
    }
    $apiRequest = $event->getApiRequest();
    if ($apiRequest['version'] == 4) {
      if ($apiRequest['entity'] === 'Contribution' && $apiRequest['action'] === 'continueCheckout') {
        if (\CRM_Core_Permission::check('make online contributions')) {
          $event->authorize();
          $event->stopPropagation();
        }
      }
    }
  }

}
