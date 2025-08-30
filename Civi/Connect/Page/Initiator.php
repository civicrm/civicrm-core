<?php

namespace Civi\Connect\Page;

/**
 * Begin a workflow to set an API key. This will redirect to the appropriate setup screen.
 *
 * This would canonically build on OAuthClient.authorizationCode() for an existing OAuthClient, but it could be another
 * variant.
 *
 * Usage: /civicrm/ajax/initiator?jwt={JWT(exp: INT_EPOCH, initiator: NAME, initiatorContext: ARRAY)}
 */
class Initiator {

  public static function initiate() {
    throw new \RuntimeException("Phasing out?");

    $rawJwt = \CRM_Utils_Request::retrieve('jwt', 'String');
    $jwt = \Civi::service('crypto.jwt')->decode($rawJwt);
    if (empty($jwt['initiator']) || !isset($jwt['initiatorContext'])) {
      throw new \CRM_Core_Exception('Invalid JWT');
    }

    // Caste to array
    $context = json_decode(json_encode($jwt['initiatorContext']), TRUE);

    $initiators = \Civi\Connect\Initiators::create($context);
    $initiator = $initiators->get($jwt['initiator']);
    if ($initiator === NULL) {
      throw new \CRM_Core_Exception('Cannot initialize API key. Unknown initiator.');
    }

    $resources = \CRM_Core_Region::instance('initiator' . hash('sha256', random_bytes(16)));
    \Civi\Core\Resolver::singleton()->call($initiator['callback'], [$context, $initiator, $resources]);
    return $resources->render('');
  }

}
