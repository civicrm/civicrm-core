<?php

namespace Civi;

use CRM_Recaptcha_ExtensionUtil as E;
use Civi\Afform\AHQ;
use Civi\Afform\Event\AfformValidateEvent;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides ReCaptcha2 validation element to Afform
 * @internal
 * @service
 */
class AfformReCaptcha2 extends AutoService implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.afform_admin.metadata' => ['onAfformGetMetadata'],
      'hook_civicrm_alterAngular' => ['alterAngular'],
      'civi.afform.validate' => ['onAfformValidate'],
    ];
  }

  public static function onAfformGetMetadata(GenericHookEvent $event) {
    $event->elements['recaptcha2'] = [
      'title' => E::ts('ReCaptcha2'),
      'afform_type' => ['form'],
      'directive' => 'crm-recaptcha2',
      'admin_tpl' => '~/afGuiRecaptcha2/afGuiRecaptcha2.html',
      'element' => [
        '#tag' => 'crm-recaptcha2',
      ],
    ];
  }

  public static function alterAngular(GenericHookEvent $event) {
    $changeSet = \Civi\Angular\ChangeSet::create('reCaptcha2')
      ->alterHtml(';\\.aff\\.html$;', function($doc, $path) {
        // Add publicKey to recaptcha elements
        foreach (pq('crm-recaptcha2') as $captcha) {
          $recaptchaPublicKey = \Civi\Api4\Setting::get(FALSE)
            ->addSelect('recaptchaPublicKey')
            ->execute()->first()['value'];
          pq($captcha)->attr('recaptchakey', $recaptchaPublicKey ?: 'foo');
        }
      });
    $event->angular->add($changeSet);
  }

  public static function onAfformValidate(AfformValidateEvent $event) {
    $layout = AHQ::makeRoot($event->getAfform()['layout']);
    if (AHQ::getTags($layout, 'crm-recaptcha2')) {
      $response = $event->getApiRequest()->getValues()['extra']['recaptcha2'] ?? NULL;
      if (!isset($response) || !\CRM_Utils_ReCAPTCHA::checkResponse($response)) {
        $event->setError(E::ts('Please go back and complete the CAPTCHA at the bottom of this form.'));
      }
    }
  }

}
