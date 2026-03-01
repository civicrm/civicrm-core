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
 * Write log messages to the civicrm_job_log table.
 */
class CRM_Core_JobLogger extends \Psr\Log\AbstractLogger {

  /**
   * Logs with an arbitrary level.
   *
   * @param string $level
   * @param string $message
   * @param array{job:\CRM_Core_ScheduledJob,source:?string,effective:?array,singleRun:?array} $context
   */
  public function log($level, $message, array $context = []): void {
    $dao = new CRM_Core_DAO_JobLog();
    $dao->domain_id = CRM_Core_Config::domainID();

    $description = CRM_Utils_String::ellipsify(strip_tags($message), 255, ' (...)');
    $dao->description = $description;

    if (!empty($context['job'])) {
      $job = $context['job'];
      $dao->job_id = $job->id;
      $dao->name = $job->name;
      $dao->command = $job->api_entity . '.' . $job->api_action;

      // Store these details in human-and-machine-readable JSON.
      $data = [
        'logLevel' => $level,
        'message' => $message,
        'jobParameters' => $job->parameters,
        'source' => empty($context['source'])
        ? NULL
        : ($context['singleRun']['parameters'] ?? NULL),
        'effectiveParameters' => $context['effective']['parameters'] ?? NULL,
      ];
      $dao->data = json_encode(array_filter($data), JSON_PRETTY_PRINT);
    }

    $dao->save();
  }

}
