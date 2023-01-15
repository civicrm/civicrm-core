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
 * (OBSOLETE) This class previously handled SOAP requests.
 *
 * The class is still referenced in some other repos. A stub is preserved to avoid hard-crashes
 * when scanning the codebase.
 *
 * @deprecated
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_SoapServer {

  public function __call($name, $arguments) {
    throw new \SoapFault('obsolete', 'SOAP support is no longer included with civicrm-core.');
    // It's removed because (a) the main consumer is no longer live, (b) it's awkward to maintain 'extern/' scripts,
    // and (c) there's an extensionized version at https://lab.civicrm.org/extensions/civismtp/
  }

}
