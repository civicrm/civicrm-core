<?php
namespace Civi\Api4\Action\OAuthClient;

use Civi\OAuth\OAuthTokenFacade;
use Civi\OAuth\OAuthException;

/**
 * Class AbstractGrantAction
 * @package Civi\Api4\Action\OAuthClient
 *
 * @method $this setStorage(string $storage)
 * @method string getStorage()
 * @method $this setTag(string $tag)
 * @method string getTag()
 */
abstract class AbstractGrantAction extends \Civi\Api4\Generic\AbstractBatchAction {

  /**
   * List of permissions to request from the OAuth service.
   *
   * If none specified, uses a default based on the client and provider.
   *
   * @var array|null
   */
  protected $scopes = NULL;

  /**
   * Where to store tokens once they are received.
   *
   * @var string
   */
  protected $storage = 'OAuthSysToken';

  /**
   * Optionally tag the new token with a symbolic/freeform label. This tag can be
   * used by automated mechanism to lookup/select a token.
   *
   * @var string|null
   */
  protected $tag = NULL;

  /**
   * The active client definition.
   *
   * @var array|null
   * @see \Civi\Api4\OAuthClient::get()
   */
  private $clientDef = NULL;

  /**
   * @throws \CRM_Core_Exception
   */
  protected function validate() {
    if (!preg_match(OAuthTokenFacade::STORAGE_TYPES, $this->storage)) {
      throw new \CRM_Core_Exception("Invalid token storage ($this->storage)");
    }
  }

  protected function getSelect() {
    return ['*'];
  }

  /**
   * Look up the definition for the desired client.
   *
   * @return array
   *   The OAuthClient details
   * @see \Civi\Api4\OAuthClient::get()
   * @throws OAuthException
   */
  protected function getClientDef():array {
    if ($this->clientDef !== NULL) {
      return $this->clientDef;
    }

    $records = $this->getBatchRecords();
    if (count($records) !== 1) {
      throw new OAuthException(sprintf("OAuth: Failed to locate client. Expected 1 client, but found %d clients.", count($records)));
    }

    $this->clientDef = array_shift($records);
    return $this->clientDef;
  }

  /**
   * @return \League\OAuth2\Client\Provider\AbstractProvider
   */
  protected function createLeagueProvider() {
    $localOptions = [];
    if ($this->scopes !== NULL) {
      $localOptions['scopes'] = $this->scopes;
    }
    return \Civi::service('oauth2.league')->createProvider($this->getClientDef(), $localOptions);
  }

  /**
   * @return array|null
   */
  public function getScopes() {
    return $this->scopes;
  }

  /**
   * @param array|string|null $scopes
   */
  public function setScopes($scopes) {
    $this->scopes = is_string($scopes) ? [$scopes] : $scopes;
    return $this;
  }

}
