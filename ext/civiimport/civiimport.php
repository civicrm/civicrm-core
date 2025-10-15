<?php

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\UserJob;
use Civi\BAO\Import;

require_once 'civiimport.civix.php';
// phpcs:disable
use Civi\Api4\Event\Subscriber\ImportSubscriber;
use CRM_Civiimport_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 *
 * @noinspection PhpUnused
 */
function civiimport_civicrm_config(&$config) {
  _civiimport_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function civiimport_civicrm_install() {
  _civiimport_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function civiimport_civicrm_enable() {
  _civiimport_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare each import table as an entity type. This function
 * was intended to be in the ImportSubscriber class but kept
 * getting errors when it was there so it's here, at least for now.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function civiimport_civicrm_entityTypes(array &$entityTypes): void {
  // This is the uncached function :-( Because we can't tell if it is being
  // called pre-boot. Currently both this and the cached functions rely on the
  // static cache - but since it keeps changing practice is to call this
  // function when we know caching is likely to be scary.
  $importEntities = _civiimport_civicrm_get_import_tables();

  foreach ($importEntities as $userJobID => $table) {
    $entityTypes['Import_' . $userJobID] = [
      'name' => 'Import_' . $userJobID,
      'class' => Import::class,
      'table' => $table['table_name'],
    ];
  }
}

/**
 * Get the available import tables.
 *
 * Note this lives here as `entityTypes` hook calls it - which may not fully
 * have class loading set up by the time it runs.
 *
 * Where the database is fully booted already it is better to call
 * `Civi\BAO\Import::getImportTables()` which is expected to have caching.
 *
 * Currently both functions share the Civi::statics caching in this function -
 * but we have had lots of back & forth so the principle is - call this if
 * we know caching could be scary - call the other for 'whatever caching is
 * most performant'.
 *
 * @return array
 */
function _civiimport_civicrm_get_import_tables(): array {
  if (isset(Civi::$statics['civiimport_tables'])) {
    return Civi::$statics['civiimport_tables'];
  }
  // We need to avoid the api here as it is called early & could cause loops.
  $tables = CRM_Core_DAO::executeQuery('
    SELECT `user_job`.`id` AS id, `metadata`, `user_job`.`name`, `user_job`.`label`, `job_type`, `user_job`.`created_id`, `created_id`.`display_name`, `user_job`.`created_date`, `user_job`.`expires_date`, `ss`.`api_entity` as entity
    FROM civicrm_user_job user_job
    LEFT JOIN civicrm_contact created_id ON created_id.id = user_job.created_id
    LEFT JOIN civicrm_search_display sd ON sd.id = user_job.search_display_id
    LEFT JOIN civicrm_saved_search ss ON ss.id = sd.saved_search_id
      -- As of writing expires date is probably not being managed
      -- it is intended to be used to actually purge the record in
      -- a cleanup job so it might not be relevant here & perhaps this will
      -- be removed later
      WHERE (user_job.expires_date IS NULL OR user_job.expires_date > NOW())
      -- this is a short-cut for looking up if they are imports
      -- it is a new convention, at best, to require anything
      -- specific in the job_type, but it saves any onerous lookups
      -- in a function which needs to avoid loops
      AND job_type LIKE "%import%"
      -- also more of a feature than a specification - but we need a table
      -- to do this pseudo-api
      AND metadata LIKE "%table_name%"');
  $importEntities = [];
  while ($tables->fetch()) {
    $tableName = json_decode($tables->metadata, TRUE)['DataSource']['table_name'];
    if (!$tableName || !CRM_Utils_Rule::alphanumeric($tableName) || !CRM_Core_DAO::singleValueQuery('SHOW TABLES LIKE %1', [1 => [$tableName, 'String']])) {
      continue;
    }
    $createdBy = !$tables->display_name ? '' : ' (' . E::ts('created by %1', [1 => $tables->display_name]) . ')';
    $importEntities[$tables->id] = [
      'table_name' => $tableName,
      'created_by' => $tables->display_name,
      'created_id' => $tables->created_id ? (int) $tables->created_id : NULL,
      'job_type' => $tables->job_type,
      'user_job_id' => (int) $tables->id,
      'created_date' => $tables->created_date,
      'expires_date' => $tables->expires_date,
      'title' => $tables->label ? E::ts('Import: %1', [1 => $tables->label]) : E::ts('Import Job %1', [1 => $tables->id]),
      'description' => $tables->created_date . $createdBy,
      'entity' => $tables->entity,
    ];
  }
  Civi::$statics['civiimport_tables'] = $importEntities;
  return $importEntities;
}

/**
 * Alter the template for the contribution import mapping to use angular form.
 *
 * @param string $formName
 * @param \CRM_Core_Form $form
 * @param string $type
 * @param string $templateFile
 *
 * @noinspection PhpUnusedParameterInspection
 * @throws \CRM_Core_Exception
 */
function civiimport_civicrm_alterTemplateFile($formName, $form, $type, &$templateFile): void {
  if ($formName === 'CRM_Queue_Page_Monitor') {
    $jobName = CRM_Utils_Request::retrieveValue('name', 'String');
    if (str_starts_with($jobName, 'user_job_')) {
      try {
        $userJobID = (int) str_replace('user_job_', '', $jobName);
        $jobType = UserJob::get()->addWhere('id', '=', $userJobID)
          ->execute()->first()['job_type'];
        foreach (CRM_Core_BAO_UserJob::getTypes() as $userJobType) {
          if ($userJobType['id'] === $jobType
            && is_subclass_of($userJobType['class'], 'CRM_Import_Parser')
          ) {

            $templateFile = 'CRM/Import/Monitor.tpl';
            Civi::resources()
              ->addVars('civiimport', ['url' => CRM_Utils_System::url('civicrm/import/contact/summary', ['reset' => 1, 'user_job_id' => $userJobID])]);
            break;
          }
        }
      }
      catch (UnauthorizedException $e) {
        // We will not do anything here if not permissioned - leave it for the core page.
      }
    }
  }
}

/**
 * Implements search tasks hook to add the `validate` and `import` actions.
 *
 * @param array $tasks
 * @param bool $checkPermissions
 * @param int|null $userId
 *
 * @noinspection PhpUnused
 */
function civiimport_civicrm_searchKitTasks(array &$tasks, bool $checkPermissions, ?int $userId) {
  foreach (Import::getImportTables() as $import) {
    $tasks['Import_' . $import['user_job_id']]['validate'] = [
      'title' => E::ts('Validate'),
      'icon' => 'fa-check',
      'apiBatch' => [
        'action' => 'validate',
        'params' => NULL,
        'runMsg' => E::ts('Validating %1 row/s...'),
        'successMsg' => E::ts('Ran validation on %1 row/s.'),
        'errorMsg' => E::ts('An error occurred while attempting to validate %1 row/s.'),
      ],
    ];
    $tasks['Import_' . $import['user_job_id']]['import'] = [
      'title' => E::ts('Import'),
      'icon' => 'fa-arrow-right',
      'apiBatch' => [
        'action' => 'import',
        'params' => NULL,
        'runMsg' => E::ts('Importing %1 row/s...'),
        'confirmMsg' => E::ts('Are you sure you want to import %1 row/s?'),
        'successMsg' => E::ts('Ran import on %1 row/s.'),
        'errorMsg' => E::ts('An error occurred while attempting to import %1 row/s.'),
      ],
    ];
  }
}

/**
 * Load the angular app for our form.
 *
 * @param string $formName
 * @param CRM_Contribute_Import_Form_MapField $form
 *
 * @throws \CRM_Core_Exception
 */
function civiimport_civicrm_buildForm(string $formName, $form) {
  //@todo - do for all Preview forms - just need to fix each Preview.tpl to
  // not open in new tab as they are not yet consolidated into one file.
  // (Or consolidate them now).
  if ($formName === 'CRM_Contact_Import_Form_Summary') {
    $form->assign('isOpenResultsInNewTab', TRUE);
    $form->assign('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/search', '', TRUE, '/display/Import_' . $form->getUserJobID() . '/Import_' . $form->getUserJobID() . '?_status=ERROR', FALSE));
    $form->assign('allRowsUrl', CRM_Utils_System::url('civicrm/search', '', TRUE, '/display/Import_' . $form->getUserJobID() . '/Import_' . $form->getUserJobID(), FALSE));
    $form->assign('importedRowsUrl', CRM_Utils_System::url('civicrm/search', '', TRUE, '/display/Import_' . $form->getUserJobID() . '/Import_' . $form->getUserJobID() . '?_status=IMPORTED', FALSE));
    try {
      $userJob = $form->getUserJob();
      if (!empty($userJob['label'])) {
        $form->setTitle(ts('Import: %1', [1 => $userJob['label']]));
      }
    }
    catch (Exception $e) {
    }
  }
}
