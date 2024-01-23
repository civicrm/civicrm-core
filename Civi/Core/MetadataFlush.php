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

namespace Civi\Core;

/**
 * Event subscriber for metadata cache clear events.
 */
class MetadataFlush extends Service\AutoSubscriber {

  public static function getSubscribedEvents() {
    return [
      'civi.cache.metadata.clear' => 'onClearMetadata',
    ];
  }

  /**
   * When metadata is flushed, client-side resources also must be refreshed.
   *
   * This uses the Resources::resetCacheCode() method which doesn't delete any files
   * from the assetBuilder directory, unlike the AssetBuilder::clear() method which does.
   * This is to avoid concurrency issues where the user tries to download a resource
   * as it's being deleted.
   */
  public static function onClearMetadata(): void {
    // This seems to cause problems during unit test setup
    if (CIVICRM_UF === 'UnitTests') {
      return;
    }
    if (\Civi\Core\Container::singleton()->has('resources')) {
      \Civi::resources()->resetCacheCode();
    }
  }

}
