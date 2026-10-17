<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;
use CRM_OAuth_ExtensionUtil as E;

/**
 * Expose the OAuth client behind a mail account as a readable field.
 *
 * The link is the token's tag rather than a foreign key, so it cannot be declared in
 * the schema; this makes it selectable and filterable anyway.
 *
 * @service oauth_client.spec_provider.mail_settings
 */
class MailSettingsOAuthSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  public function modifySpec(RequestSpec $spec): void {
    $field = (new FieldSpec('oauth_client_id', $spec->getEntity(), 'Integer'))
      ->setLabel(E::ts('OAuth Client'))
      ->setTitle(E::ts('OAuth Client'))
      ->setColumnName('id')
      ->setDescription(E::ts('The OAuth client which supplies credentials for this account'))
      ->setType('Extra')
      ->setReadonly(TRUE)
      ->setOptionsCallback([__CLASS__, 'getClients'])
      ->setSqlRenderer([__CLASS__, 'renderOAuthClientId']);
    $spec->addFieldSpec($field);
  }

  public function applies($entity, $action): bool {
    return $entity === 'MailSettings' && $action === 'get';
  }

  /**
   * The newest token for this account names the live client.
   *
   * @param array $field
   * @return string
   */
  public static function renderOAuthClientId(array $field): string {
    $id = $field['sql_name'];
    $prefix = \Civi\OAuth\OAuthMailSettingsTag::TOKEN_TAG_PREFIX;
    return "(SELECT `oauth_systoken`.`client_id` FROM `civicrm_oauth_systoken` `oauth_systoken`
      WHERE `oauth_systoken`.`tag` = CONCAT('$prefix', $id)
      ORDER BY `oauth_systoken`.`id` DESC LIMIT 1)";
  }

  /**
   * @return array
   */
  public static function getClients(): array {
    $options = [];
    $providers = \Civi\Api4\OAuthProvider::get(FALSE)->execute()->indexBy('name');
    foreach (\Civi\Api4\OAuthClient::get(FALSE)->addSelect('id', 'provider')->execute() as $client) {
      $provider = $providers[$client['provider']] ?? NULL;
      $options[] = [
        'id' => $client['id'],
        'name' => $client['provider'] . ':' . $client['id'],
        'label' => E::ts('%1 (client #%2)', [
          1 => $provider['title'] ?? $client['provider'],
          2 => $client['id'],
        ]),
      ];
    }
    return $options;
  }

}
