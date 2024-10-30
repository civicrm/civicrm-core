#!/usr/bin/env php
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


require_once 'cli.class.php';
$cli = new civicrm_Cli();
$cli->initialize() || die('Died during initialization');
$cli->callApi() || die('Died during callApi');
