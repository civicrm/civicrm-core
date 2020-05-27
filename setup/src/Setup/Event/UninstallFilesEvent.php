<?php
namespace Civi\Setup\Event;

/**
 * Purge any CiviCRM support files, such as `civicrm.settings.php` or `templateCompileDir`
 *
 * Event Name: 'civi.setup.uninstallFiles'
 */
class UninstallFilesEvent extends BaseSetupEvent {
}
