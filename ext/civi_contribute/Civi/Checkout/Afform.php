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
      // add afCheckout as a dependency of forms with Contributions
      // we must hook later to alter afform modules @see afform_civicrm_config
      'hook_civicrm_angularModules' => ['onAlterAngularModules', -1010],
      // provide CheckoutBlock element to FormBuilder
      'civi.afform_admin.metadata' => ['onAfformAdminMetadata'],
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

    // add afCheckout as a dependency of afAdmin so can be used in FormBuilder
    $e->angularModules['afAdmin']['requires'][] = 'afCheckout';

    // find configured afforms which use checkout and add the module dependency
    // TODO: if/when we have a hook for afforms being saved, we should include
    // the requirement in the .ang.php data in order to avoid computing it
    // for every form every cache clear
    foreach ($e->angularModules as $name => $module) {
      if (!empty($module['_afform']) && $this->afformUsesCheckout($module['_afform'])) {
        $e->angularModules[$name]['requires'] ??= [];
        $e->angularModules[$name]['requires'][] = 'afCheckout';
      }
    }
  }

  private function afformUsesCheckout(string $afformName): bool {
    $layout = \Civi\Api4\Afform::get(FALSE)
      ->addWhere('name', '=', $afformName)
      ->addSelect('layout')
      ->setLayoutFormat('html')
      ->execute()
      ->first()['layout'] ?? '';

    if (!\str_contains($layout, 'Contribution')) {
      return FALSE;
    }
    return TRUE;

    // the below is a more strictly correct check - but is more labour intensive
    // $layout = \Civi\Api4\Afform::get(FALSE)
    //   ->addWhere('name', '=', $afformName)
    //   ->addSelect('layout')
    //   ->setLayoutFormat('deep')
    //   ->execute()
    //   ->first()['layout'] ?? NULL;

    // // unexpected but dont crash the hook
    // if (!$layout) {
    //   return FALSE;
    // }

    // // does the form include contributions?
    // $formDataModel = new FormDataModel($layout);
    // $contributions = array_filter($formDataModel->getEntities(), fn ($entity) => $entity['type'] === 'Contribution');

    // return !!$contributions;
  }

  public function onAfformAdminMetadata(GenericHookEvent $e) {
    if (!$this->isActive()) {
      return;
    }
    $e->inputTypes[] = [
      'name' => 'CheckoutBlock',
      'label' => E::ts('Checkout Details Block'),
      'template' => '~/afCheckout/inputType/CheckoutBlock.html',
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
