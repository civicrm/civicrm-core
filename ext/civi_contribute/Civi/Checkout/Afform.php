<?php

namespace Civi\Checkout;

use Civi\Afform\Event\AfformSubmitEvent;
use Civi\Afform\Event\AfformValidateEvent;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoService;
use CRM_Contribute_ExtensionUtil as E;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handle afform integration with Checkout
 *
 * @service civi.checkout.afform
 */
class Afform extends AutoService implements EventSubscriberInterface {

  public const NO_PAYMENT_PROCESSING = '_none_';

  public function isActive(): bool {
    return !!\Civi::settings()->get('contribute_enable_afform_contributions');
  }

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      // add CheckoutOption modules as dependencies of afCheckout
      'hook_civicrm_angularModules' => ['onAlterAngularModules', -1010],
      // provide CheckoutBlock element to FormBuilder
      'civi.afform.input_types' => ['onAfformInputTypes'],
      // run validation hook from selected CheckoutOption
      'civi.afform.validate' => ['validatePaymentFields', 0],
      // trigger Checkout on submission
     //  this runs *after* the Contribution is saved at 0
      'civi.afform.submit' => ['startCheckout', -100],
    ];
  }

  /**
   * Validate the payment fields on the form
   *
   * @param \Civi\Afform\Event\AfformValidateEvent $event
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function validatePaymentFields(AfformValidateEvent $event) {
    if (!$this->isActive()) {
      return;
    }

    $contributions = array_filter($event->getFormDataModel()->getEntities(), fn ($entity) => $entity['type'] === 'Contribution');

    if (!$contributions) {
      return;
    }

    $values = $event->getSubmittedValues();

    // NOTE: we only expect one Contribution, but future proofing
    foreach ($contributions as $contribution) {
      foreach ($values[$contribution['name']] as $contributionValues) {
        $checkoutOption = $contributionValues['fields']['checkout_option'] ?? NULL;
        if (!$checkoutOption) {
          continue;
        }

        $checkoutOption = \Civi::service('civi.checkout')->getOption($checkoutOption);

        // TODO: what is passed to the checkout validate?
        // this is much harder than the actual payment - when the records are saved and
        // we can pass the Contribution ID and the processor can get whatever info it needs
        // using canonical api4 keys
        // but the data structure at this point is very afform specific
        $checkoutOption->validate($event);
      }
    }
  }

  /**
   * Check if Payment Processor is configured. If it is, start CheckoutSession
   * and add response to the afform response
   */
  public function startCheckout(AfformSubmitEvent $event) {
    if (!$this->isActive()) {
      return;
    }

    if ($event->getEntityType() !== 'Contribution') {
      return;
    }

    // get contribution record
    $contribution = $event->getRecords()[0]['fields'];

    // check for payment processor value: if set this will trigger checkout, if not we will ignore
    $checkoutOption = $contribution['checkout_option'] ?? NULL;
    if (!$checkoutOption) {
      return;
    }
    // get contribution ID (the record should already have been saved in CreateContribution)
    $contributionId = $event->getEntityId(0);

    $checkoutSession = new CheckoutSession($contributionId, $checkoutOption);

    // get payment params passed from frontend
    $checkoutSession->setCheckoutParams($contribution['checkout_params'] ?? []);

    $this->setOnwardUrlsAndMessages($checkoutSession, $event);

    // start the checkout
    $checkoutSession->startCheckout();

    $responseItems = $checkoutSession->getResponseItems();

    $submitRequest = $event->getApiRequest();

    foreach ($responseItems as $key => $value) {
      $submitRequest->setResponseItem($key, $value);
    }
  }

  /**
   * Set onward urls for the checkout session based on afform configuration
   *
   * At the moment this is limited to using the Afform "redirect_url" as success
   * page if set.
   *
   * For now, everything else will use the default afform checkout landing page.
   *
   * TODO 1: should we add form-level redirect for Fail/Cancel states (this could be
   *   independent of checkout)
   *
   * TODO 2: a good default behaviour for Fail could be to resume the form state - it
   *   may be there is just one thing you need to correct in the details you entered
   *
   * NOTE: we currently force https urls because at least stripe checkout requires
   *   this - this could cause errors if SSL is not configured - we should warn about
   *   this at a higher level (system check?)
   */
  private function setOnwardUrlsAndMessages(CheckoutSession $checkout, AfformSubmitEvent $event): void {
    $afform = $event->getAfform();

    $checkout->setTitle($afform['title']);

    $confirmationUrl = ($afform['confirmation_type'] === 'redirect_to_url') ? $afform['redirect'] : NULL;
    if ($confirmationUrl) {
      $confirmationUrl = $event->getApiRequest()->replaceTokens($confirmationUrl);

      $confirmationUrl = (string) \Civi::url($confirmationUrl)
        ->setPreferFormat('absolute')
        ->setSsl(TRUE);

      $checkout->setSuccessUrl($confirmationUrl);
      return;
    }
    $confirmationMessage = ($afform['confirmation_type'] === 'show_confirmation_message') ? $afform['confirmation_message'] : NULL;
    if ($confirmationMessage) {
      $confirmationMessage = $event->getApiRequest()->replaceTokens($confirmationMessage);

      $checkout->setSuccessMessage($confirmationMessage);
      return;
    }
  }

  public function onAlterAngularModules(GenericHookEvent $e): void {
    if (!$this->isActive()) {
      // if afform contributions is disabled we de-register afCheckout
      unset($e->angularModules['afCheckout']);

      // also deregister any modules which depend on it (which will come from integrations, e.g. afStripe, afPaypal)
      // NOTE: if another module registers a dependency on afStripe it might crash. we dont expect that to happen
      // and in the long run this should be removed, so not worth handling that case
      foreach ($e->angularModules as $name => $module) {
        if (in_array('afCheckout', $module['requires'] ?? [])) {
          unset($e->angularModules[$name]);
        }
      }
      return;
    }

    $checkoutOptions = \Civi::service('civi.checkout')->getOptions();

    // if no checkout options configured then no need to do anything
    if (!$checkoutOptions) {
      return;
    }

    foreach ($checkoutOptions as $name => $option) {
      $module = $option->getAfformModule() ?? NULL;
      if ($module && !in_array($module, $e->angularModules['afCheckout']['requires'])) {
        $e->angularModules['afCheckout']['requires'][] = $module;
      }
    }
  }

  public function onAfformInputTypes(GenericHookEvent $e) {
    if (!$this->isActive()) {
      return;
    }
    $e->inputTypes['CheckoutBlock'] = [
      'label' => E::ts('Checkout Details Block'),
      'template' => '~/afCheckout/inputType/CheckoutBlock.html',
      // Todo: Split the admin stuff into a different module
      'admin_template' => '~/afCheckout/inputType/CheckoutBlockAdmin.html',
    ];
  }

  public static function getSettings(): array {
    $checkoutOptions = \Civi::service('civi.checkout')->getOptions();
    $testMode = \Civi::service('civi.checkout')->isTestMode();

    $config = array_filter(array_map(fn ($option) => $option->getAfformSettings($testMode), $checkoutOptions));

    return [
      'checkoutOptions' => $config,
    ];
  }

}
