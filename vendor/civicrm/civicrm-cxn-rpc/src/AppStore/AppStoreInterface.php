<?php
namespace Civi\Cxn\Rpc\AppStore;

use Civi\Cxn\Rpc\AppMeta;

interface AppStoreInterface {

  /**
   * @return array
   *   List of App IDs.
   */
  public function getAppIds();

  /**
   * @param string $appId
   *   The application's globally unique ID.
   * @return array
   *   The application metadata.
   * @see AppMeta
   */
  public function getAppMeta($appId);

  /**
   * @param string $appId
   *   The application's globally unique ID.
   * @return string
   *   PEM-encoded.
   */
  public function getPublicKey($appId);

  /**
   * @param string $appId
   *   The application's globally unique ID.
   * @return string
   *   PEM-encoded.
   */
  public function getPrivateKey($appId);

  /**
   * @param string $appId
   *   The application's globally unique ID.
   * @return array
   *   Array with elements:
   *     - publickey: string, pem.
   *     - privatekey: string, pem
   */
  public function getKeyPair($appId);
}
