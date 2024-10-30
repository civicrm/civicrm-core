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
 *
 * Export records in a csv format to standard out. Optionally
 * limit records by field/value pairs passed as arguments.
 *
 * Usage:
 * php bin/csv/export.php -e <entity> [ --field=value --field=value ] [ -s site.org ]
 * e.g.: php bin/csv/export.php -e Contact --email=jamie@progressivetech.org
 *
 */
require_once dirname(__DIR__) . '/cli.class.php';

$entityExporter = new civicrm_cli_csv_exporter();
$entityExporter->run();
