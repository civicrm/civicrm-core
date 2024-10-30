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
 * Delete records passed in via a csv file. You must have the record
 * id defined in the csv file.
 *
 * Usage:
 * php bin/csv/delete.php -e <entity> --file /path/to/csv/file [ -s site.org ]
 * e.g.: php bin/csv/delete.php -e Contact --file /tmp/delete.csv
 *
 */

require_once dirname(__DIR__) . '/cli.class.php';

$entityImporter = new civicrm_cli_csv_deleter();
$entityImporter->run();
