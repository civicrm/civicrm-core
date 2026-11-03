<?php

namespace Civi;

use Civi\Core\Attribute\CiviEventListener;
use CRM_Recaptcha_ExtensionUtil as E;
use Civi\Afform\AHQ;
use Civi\Afform\Event\AfformValidateEvent;
use Civi\Core\Event\GenericHookEvent;

/**
 * Provides ReCaptcha2 validation element to Afform
 */
class AfformReCaptcha2 {

  #[CiviEventListener(event: 'civi.afform_admin.metadata')]
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

  #[CiviEventListener(event: 'hook_civicrm_alterAngular')]
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

  #[CiviEventListener(event: 'civi.afform.validate')]
  public static function onAfformValidate(AfformValidateEvent $event) {
    $layout = AHQ::makeRoot($event->getAfform()['layout']);
    if (AHQ::getTags($layout, 'crm-recaptcha2')) {
      $response = $event->getApiRequest()->getValues()['extra']['recaptcha2'] ?? NULL;
      if (!isset($response) || !\CRM_Utils_ReCAPTCHA::checkResponse($response)) {
        $event->addError(E::ts('Please go back and complete the CAPTCHA at the bottom of this form.'));
      }
    }
  }

}
