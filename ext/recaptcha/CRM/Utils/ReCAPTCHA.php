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

use CRM_Recaptcha_ExtensionUtil as E;

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
  private static $_singleton = NULL;

  /**
   * Singleton function used to manage this object.
   *
   * @return CRM_Utils_ReCAPTCHA
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
    return (bool) \Civi::settings()->get('recaptchaPublicKey');
  }

  /**
   * Add element to form.
   *
   * @param CRM_Core_Form $form
   */
  private static function add(&$form) {
    $error = NULL;

    // If we already added reCAPTCHA then don't add it again.
    // The `recaptcha_get_html` function only exists once recaptchalib.php has been included via this function.
    if (function_exists('recaptcha_get_html')) {
      return;
    }
    require_once E::path('lib/recaptcha/recaptchalib.php');

    // Load the Recaptcha api.js over HTTPS
    $useHTTPS = TRUE;

    $html = recaptcha_get_html(\Civi::settings()->get('recaptchaPublicKey'), $error, $useHTTPS);

    $form->assign('recaptchaHTML', $html);
    $form->add(
      'text',
      'g-recaptcha-response',
      'reCaptcha',
      NULL,
      TRUE
    );
    $form->registerRule('recaptcha', 'callback', 'validate', 'CRM_Utils_ReCAPTCHA');
    $form->addRule('g-recaptcha-response', E::ts('Please go back and complete the CAPTCHA at the bottom of this form.'), 'recaptcha');
    if ($form->isSubmitted() && empty($form->_submitValues['g-recaptcha-response'])) {
      $form->setElementError(
        'g-recaptcha-response',
        E::ts('Please go back and complete the CAPTCHA at the bottom of this form.')
      );
    }
  }

  /**
   * Enable ReCAPTCHA on form
   *
   * DO NOT USE OUTSIDE OF CiviCRM core.
   *
   * @param CRM_Core_Form $form
   * @param bool $checkStandardConditions Check the standard conditions before adding?
   */
  public static function enableCaptchaOnForm(&$form, bool $checkStandardConditions = TRUE) {
    if ($checkStandardConditions && !self::checkStandardConditionsForEnableReCAPTCHA()) {
      return;
    }
    $captcha = CRM_Utils_ReCAPTCHA::singleton();
    if ($captcha->hasSettingsAvailable()) {
      $captcha->add($form);
      $form->assign('isCaptcha', TRUE);
      CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/common/ReCAPTCHA.tpl']);
    }
  }

  /**
   * Check the standard conditions for adding a ReCAPTCHA to the form
   *
   * @return bool
   */
  private static function checkStandardConditionsForEnableReCAPTCHA(): bool {
    if (!CRM_Core_Session::getLoggedInContactID()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * This checks the form criteria to see if reCAPTCHA should be added and then it adds it to the form if required.
   * DO NOT USE OUTSIDE OF CiviCRM core.
   *
   * @param string $formName
   * @param CRM_Core_Form $form
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function checkAndAddCaptchaToForm($formName, &$form) {
    $addCaptcha = FALSE;
    $ufGroupIDs = [];

    switch ($formName) {
      case 'CRM_Contribute_Form_Contribution_Main':
        if (\Civi::settings()->get('forceRecaptcha')) {
          $addCaptcha = TRUE;
        }
        else {
          $ufGroupIDs = $form->getUFGroupIDs();
        }
        break;

      case 'CRM_Mailing_Form_Subscribe':
      case 'CRM_Event_Cart_Form_Checkout_Payment':
        $addCaptcha = TRUE;
        break;

      case 'CRM_PCP_Form_PCPAccount':
      case 'CRM_Campaign_Form_Petition_Signature':
        $ufGroupIDs = $form->getUFGroupIDs();
        break;

      case 'CRM_Profile_Form_Edit':
        // add captcha only for create mode.
        if ($form->getIsCreateMode()) {
          $ufGroupIDs = $form->getUFGroupIDs();
        }
        break;

      case 'CRM_Event_Form_Registration_Register':
        $button = substr($form->controller->getButtonName(), -4);
        // We show reCAPTCHA for anonymous user if enabled.
        // 'skip' button is on additional participant forms, we only show reCAPTCHA on the primary form.
        if ($button !== 'skip') {
          $ufGroupIDs = $form->getUFGroupIDs();
        }
        break;

    }

    if (!empty($ufGroupIDs) && empty($addCaptcha)) {
      foreach ($ufGroupIDs as $ufGroupID) {
        $addCaptcha = \Civi\Api4\UFGroup::get(FALSE)
          ->addWhere('id', '=', $ufGroupID)
          ->execute()
          ->first()['add_captcha'];
        if ($addCaptcha) {
          break;
        }
      }
    }

    if ($addCaptcha) {
      CRM_Utils_ReCAPTCHA::enableCaptchaOnForm($form);
    }
  }

  /**
   * QuickForm validate callback
   *
   * @param $value
   * @param CRM_Core_Form $form
   *
   * @return mixed
   */
  public static function validate($value, $form) {
    return self::checkResponse($_POST['g-recaptcha-response']);
  }

  /**
   * @param string $response
   * @return bool
   */
  public static function checkResponse($response) {
    require_once E::path('lib/recaptcha/recaptchalib.php');

    $resp = recaptcha_check_answer(CRM_Core_Config::singleton()->recaptchaPrivateKey,
      CRM_Utils_System::ipAddress(),
      $response
    );
    return $resp->is_valid;
  }

}
