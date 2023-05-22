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

namespace Civi\Api4\Action\AuthxCredential;

use Civi\Api4\Generic\Result;

/**
 * Generate a security checksum for anonymous access to CiviCRM.
 *
 * @method int getContactId() Get contact ID param (required)
 * @method $this setContactId(int $contactId) Set the Contact Id
 * @method $this setTtl(int $ttl) Set TTL param
 * @method int getTtl() get the TTL param;
 */
class Create extends \Civi\Api4\Generic\AbstractAction {

  /**
   * ID of contact
   *
   * @var int
   * @required
   */
  protected $contactId;

  /**
   * Expiration time (in seconds). Defaults to 300 seconds
   *
   * @var int
   */
  protected $ttl = 300;

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $token = \Civi::service('crypto.jwt')->encode([
      'exp' => time() + $this->ttl,
      'sub' => 'cid:' . $this->contactId,
      'scope' => 'authx',
    ]);

    $result[] = [
      'cred' => 'Bearer ' . $token,
    ];
  }

}
