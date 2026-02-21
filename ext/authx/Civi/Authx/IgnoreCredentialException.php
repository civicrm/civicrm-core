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
 * You start evaluating a credential. It's not exactly valid or invalid -- but you realize that
 * the credential is so nonsensical that we can neither accept it nor reject it.
 */
class IgnoreCredentialException extends AuthxException {

}
