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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_ReCAPTCHA {

  protected $_captcha = NULL;

  protected $_name = NULL;

  protected $_url = NULL;

  protected $_phrase = NULL;

  /**
   * Singleton.
   *
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var CRM_Utils_ReCAPTCHA
   */
  static private $_singleton = NULL;

  /**
   * Singleton function used to manage this object.
   *
   * @return object
   */
  public static function &singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Utils_ReCAPTCHA();
    }
    return self::$_singleton;
  }

  /**
   * Check if reCaptcha settings is avilable to add on form.
   */
  public static function hasSettingsAvailable() {
    $config = CRM_Core_Config::singleton();
    if ($config->recaptchaPublicKey == NULL || $config->recaptchaPublicKey == "") {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Check if reCaptcha has to be added on form forcefully.
   */
  public static function hasToAddForcefully() {
    $config = CRM_Core_Config::singleton();
    if (!$config->forceRecaptcha) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Add element to form.
   *
   * @param CRM_Core_Form $form
   */
  public static function add(&$form) {
    $error = NULL;
    $config = CRM_Core_Config::singleton();
    $useSSL = FALSE;
    if (!function_exists('recaptcha_get_html')) {
      require_once 'packages/recaptcha/recaptchalib.php';
    }

    // Load the Recaptcha api.js over HTTPS
    $useHTTPS = TRUE;

    $html = recaptcha_get_html($config->recaptchaPublicKey, $error, $useHTTPS);

    $form->assign('recaptchaHTML', $html);
    $form->assign('recaptchaOptions', $config->recaptchaOptions);
    $form->add(
      'text',
      'g-recaptcha-response',
      'reCaptcha',
      NULL,
      TRUE
    );
    $form->registerRule('recaptcha', 'callback', 'validate', 'CRM_Utils_ReCAPTCHA');
    if ($form->isSubmitted() && empty($form->_submitValues['g-recaptcha-response'])) {
      $form->setElementError(
        'g-recaptcha-response',
        ts('Please go back and complete the CAPTCHA at the bottom of this form.')
      );
    }
  }

  /**
   * Enable ReCAPTCHA on Contribution form
   *
   * @param CRM_Core_Form $form
   */
  public static function enableCaptchaOnForm(&$form) {
    $captcha = CRM_Utils_ReCAPTCHA::singleton();
    if ($captcha->hasSettingsAvailable()) {
      $captcha->add($form);
      $form->assign('isCaptcha', TRUE);
    }
  }

}
