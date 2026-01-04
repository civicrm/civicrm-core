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

namespace Civi\Core\Security;

/**
 * The `phar://` handler decides when/if to load data from PHAR files.
 * The helpers can inspect the PHAR handling and register a preferred PHAR handler.
 */
class PharLoader {

  public static function isWrapperInstantiated(): bool {
    try {
      \TYPO3\PharStreamWrapper\Manager::instance();
      return TRUE;
    }
    catch (\LogicException $e) {
      if ($e->getCode() === 1535189872) {
        return FALSE;
      }
      else {
        // this is not an exception we can handle
        throw $e;
      }
    }
  }

  /**
   * Does the system have any kind of support for reading phar://?
   * Or has it been completely disabled?
   *
   * - This will return TRUE in a vanilla PHP process (with default handler).
   * - It will also return TRUE if someone (such as Drupal, Joomla, or CiviCRM) has registered a wrapper.
   * - However, it can return FALSE if someone (such as Backdrop Web) has disabled support.
   */
  public static function isPharLoadingEnabled(): bool {
    return in_array('phar', stream_get_wrappers());
  }

  /**
   * Register an alternative phar:// stream wrapper to filter out insecure Phars
   *
   * PHP makes it possible to trigger Object Injection vulnerabilities by using
   * a side-effect of the phar:// stream wrapper that unserializes Phar
   * metadata. To mitigate this vulnerability, projects such as TYPO3 and Drupal
   * have implemented an alternative Phar stream wrapper that disallows
   * inclusion of phar files based on certain parameters.
   *
   * This code attempts to register the TYPO3 Phar stream wrapper using the
   * interceptor defined in \Civi\Core\Security\PharExtensionInterceptor. In an
   * environment where the stream wrapper was already registered via
   * \TYPO3\PharStreamWrapper\Manager (i.e. Drupal), this code does not do
   * anything. In other environments (e.g. WordPress, at the time of this
   * writing), the TYPO3 library is used to register the interceptor to mitigate
   * the vulnerability.
   */
  public static function register() {
    \TYPO3\PharStreamWrapper\Manager::initialize(
      (new \TYPO3\PharStreamWrapper\Behavior())
        ->withAssertion(new \Civi\Core\Security\PharExtensionInterceptor())
    );

    // The default phar:// handler may be live (as on Standalone/WordPress), or it may
    // have been removed preemptively (as on Backdrop). Either way, we want to replace it.
    if (static::isPharLoadingEnabled()) {
      stream_wrapper_unregister('phar');
    }
    stream_wrapper_register('phar', \TYPO3\PharStreamWrapper\PharStreamWrapper::class);
  }

}
