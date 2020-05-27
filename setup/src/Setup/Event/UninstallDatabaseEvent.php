<?php
namespace Civi\Setup\Event;

/**
 * Purge any CiviCRM schema (tables, views, functions) from the database.
 *
 * Event Name: 'civi.setup.uninstallDatabase'
 */
class UninstallDatabaseEvent extends BaseSetupEvent {
}
