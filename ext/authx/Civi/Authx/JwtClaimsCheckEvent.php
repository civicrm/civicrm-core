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

namespace Civi\Authx;

/**
 * Class JwtClaimsCheckEvent. Dispatched when AuthX has decoded a JWT, but
 * before the scope and sub claims have been checked, allowing those checks to
 * be overriden.
 *
 * The primary motivation is to allow matching the identity from a different
 * provider to a contact id, such as with Auth0.
 */
class JwtClaimsCheckEvent extends \Civi\Core\Event\GenericHookEvent {

  /**
   * List of validated JWT claims
   *
   * @var array
   */
  public $claims;

  private $overrides = [];

  /**
   * @param array $claims List of validated JWT claims (I.e. decoded). Expected
   * to contain at least 'sub' and 'scope' keys.
   */
  public function __construct(array $claims) {
    $this->claims = $claims;
  }

  /**
   * Reject the scope claim, using provided message in error response body. This
   * will override the default AuthX Authenticator handling of the scope.
   *
   * @param   string $message
   */
  public function rejectScope(string $message) {
    $this->overrides['scope'] = ['reject' => TRUE, 'message' => $message];
  }

  /**
   * Accept the scope claim. This will override the default AuthX Authenticator
   * handling of the scope.
   */
  public function acceptScope() {
    $this->overrides['scope'] = ['reject' => FALSE];
  }

  /**
   * Reject the sub claim, using provided message in error response body. This
   * will override the default AuthX Authenticator handling of the sub and
   * retrieval of the civicrm contact id.
   *
   * @param   string $message
   */
  public function rejectSub(string $message) {
    $this->overrides['sub'] = ['reject' => TRUE, 'message' => $message];
  }

  /**
   * Accept the sub claim, matching the credentials to a specific user by
   * civicrm contact id ('contactId'), CRM user id ('userId') or CRM username
   * ('user'). This will cause authx to log in that user for the purposes of the
   * current request.
   *
   * This will override the default AuthX Authenticator handling of the sub
   * which expects the form "cid:{contactId}
   *
   * DETAILS ABOUT $IDENTIFIER
   * At least one of contactId, userId or user must be provided in $identifier,
   * but only one of userId or user can be be specified, not both. If user is
   * present but not contactId, it will be used to find userId, and then
   * contactId. If userId is present but not contactId, it will be used to find
   * contactId. If contactId is present but not user or userId, it will be used
   * to find userId (user isn't used this this case).
   *
   * @param   array{contactId?: int, userId?: int, user?: string} $identifier
   * Must contain at least one of contactId or (userId xor user)
   *
   */
  public function acceptSub(array $identifier) {
    if (empty($identifier['contactId']) && empty($identifier['userId']) && empty($identifier['user'])) {
      throw new AuthxException("Must specify at least one of contactId, userId or user");
    }

    if (!empty($identifier['userId']) && !empty($identifier['user'])) {
      throw new AuthxException("Only userId or user can be specified, not both");
    }

    $this->overrides['sub'] = ['reject' => FALSE] + $identifier;
  }

  /**
   * If listener has called acceptScope(), returned array  will have a key
   * 'scope' set with value of ['reject' => FALSE]
   *
   * If listener has called rejectScope(),  returned array will have a key
   * 'scope' set with value of ['reject' => TRUE, 'message' => string]
   *
   * If listener has called acceptSub(), returned array will have a key 'sub'
   * set with value of ['reject' => FALSE, 'contactId' => integer,
   * 'userId' => integer, 'user' => string]. reject will always be present,
   * while one or more of contactId, userId and userId will be set.
   *
   * If listener has called rejectSub(),  returned array will have a key 'sub'
   * set with value of ['reject' => TRUE, 'message' => string]
   *
   * If there are no listeners, or they have taken no action, will return an
   * empty array.
   *
   * @return  array   Array with keys of 'sub', 'scope', none or both.
   */
  public function getOverrides(): array {
    return $this->overrides;
  }

  /**
   * @inheritDoc
   */
  public function getHookValues() {
    return [$this->claims];
  }

}
