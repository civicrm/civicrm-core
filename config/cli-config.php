<?php

require_once 'bootstrap.php';

$civicrm_base_path = dirname(__DIR__);
$settings_file_path = CRM_Utils_Path::join($civicrm_base_path, 'tests', 'phpunit', 'CiviTest', 'civicrm.settings.dist.php');
require_once($settings_file_path);
$entity_manager = CRM_DB_EntityManager::singleton();
$platform = $entity_manager->getConnection()->getDatabasePlatform();
$platform->registerDoctrineTypeMapping('enum', 'string');
return Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entity_manager);
