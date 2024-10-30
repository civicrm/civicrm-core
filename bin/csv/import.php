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
 * Import records from a csv file passed as an argument.
 *
 * Usage:
 * php bin/csv/import.php -e <entity> --file /path/to/csv/file [ -s site.org ]
 * e.g.: php bin/csv/import.php -e Contact --file /tmp/import.csv
 *
 */
require_once dirname(__DIR__) . '/cli.class.php';

$entityImporter = new civicrm_cli_csv_importer();
$entityImporter->run();
