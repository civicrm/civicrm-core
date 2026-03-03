<?php

use CRM_OAuth_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_OAuth_Form_Start extends CRM_Core_Form {

  /**
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $state = $this->parseState();
    [$client, $provider] = $this->getClientProvider($state['clientId']);
    CRM_Utils_System::setTitle(E::ts('External Service'));

    $blurbs = [];
    $blurbs[] = '<p>' . E::ts('You are connecting to an external service, <em>%1</em>.', [1 => htmlentities($provider['title'])]) . '</p>';

    $civiConnectApproved = fn() => Civi::settings()->get('oauth_civi_connect_approved');
    $civiConnectRegistered = fn() => !Civi::service('oauth_client.civi_connect')->isRegistered($provider['options']['urlAuthorize']);

    if ($this->isCiviConnect($client) && !($civiConnectApproved() && $civiConnectRegistered())) {
      if (CRM_Core_Permission::check('manage OAuth client')) {
        $blurbs[] = '<p>' . E::ts('This process will be facilitated by <em>CiviConnect</em>, which enables simplified setup for self-managed CiviCRM sites.') . '</p>';
        $blurbs[] = '<p>' . E::ts('As you continue through this process, your browser may show pages from "CiviConnect" and/or "%1".', [1 => htmlentities($provider['title'])]) . '</p>';
        // $blurbs[] = '<ol>'
        //   . '<li>' . E::ts('<strong>CiviConnect</strong>: Approve service for your domain.') . '</li>'
        //   . '<li>' . E::ts('<strong>%1</strong>: Select your account. Review your permissions.', [1 => htmlentities($provider['title'])]) . '</li>'
        //   . '</ol>';

        if (!$civiConnectApproved()) {
          $termsUrl = \CRM_Utils_Url::toOrigin($provider['options']['urlAuthorize']) . '/terms';
          $this->assign('civiconnect_terms', $termsUrl);
          $this->add('checkbox', 'civiconnect_approved', E::ts('Enable CiviConnect'), NULL, TRUE);
        }
      }
      else {
        throw new \CRM_Core_Exception('The system administrator must approve CiviConnect.');
      }
    }

    // $blurbs[] = 'Target: <code>' . htmlentities($state['externalStartPage'])  . '</code>';
    $this->assign('blurbs', $blurbs);

    if (!Civi::settings()->get('oauth_auto_confirm') && CRM_Core_Permission::check('manage OAuth client')) {
      $this->add('checkbox', 'do_not_ask_again', E::ts('Hide this alert'));
    }

    $this->add('hidden', 'state', CRM_Utils_Request::retrieve('state', 'String'));
    $this->addButtons([
      ['type' => 'submit', 'name' => E::ts('Continue'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => E::ts('Cancel')],
    ]);

    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function postProcess(): void {
    $state = $this->parseState();
    [$client, $provider] = $this->getClientProvider($state['clientId']);
    $values = $this->exportValues();
    $civiConnect = \Civi::service('oauth_client.civi_connect');

    if (CRM_Core_Permission::check('manage OAuth client')) {
      if (!empty($values['do_not_ask_again'])) {
        Civi::settings()->set('oauth_auto_confirm', TRUE);
      }
      if (!empty($values['civiconnect_approved'])) {
        Civi::settings()->set('oauth_civi_connect_approved', TRUE);
      }
    }

    if ($this->isCiviConnect($client) && (Civi::settings()->get('oauth_civi_connect_approved'))) {
      $civiConnect->register($provider['options']['urlCiviConnect']);
    }

    CRM_Utils_System::redirect($state['externalStartPage']);
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames(): array {
    $elementNames = [];
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  protected function parseState(): array {
    $stateId = CRM_Utils_Request::retrieve('state', 'String');
    $state = \Civi::service('oauth2.state')->load($stateId);
    if (empty($state['externalStartPage']) || empty($state['clientId'])) {
      throw new \CRM_Core_Exception("Mismatched state");
    }
    return $state;
  }

  /**
   * @param int $clientId
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function getClientProvider(int $clientId): array {
    $client = \Civi\Api4\OAuthClient::get(FALSE)->addWhere('id', '=', $clientId)
      ->execute()
      ->single();
    $provider = \Civi\Api4\OAuthProvider::get(FALSE)->addWhere('name', '=', $client['provider'])
      ->execute()
      ->single();
    return [$client, $provider];
  }

  protected function isCiviConnect(array $client): bool {
    return $client['guid'] === '{civi_connect}';
  }

}
