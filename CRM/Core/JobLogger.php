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

      $dao->command = ts("Entity:") . " " . $job->api_entity . " " . ts("Action:") . " " . $job->api_action;
      // This seems weird to me - the output is a little hard to read. More punctuation?

      $data = '';
      if (!empty($job->parameters)) {
        $data .= "\n\nParameters raw (from db settings): \n" . $job->parameters;
      }
      if (!empty($context['source']) && !empty($context['singleRun']['parameters'])) {
        $data .= "\n\nParameters raw (" . $context['source'] . "): \n" . serialize($context['singleRun']['parameters']);
      }
      if (!empty($context['effective']['parameters'])) {
        $data .= "\n\nParameters parsed (and passed to API method): \n" . serialize($context['effective']['parameters']);
      }
      if ($description !== $message) {
        $data .= "\n\nFull message: \n" . $message;
        // This seems weird to me. Shouldn't you do it regardless of `job` availability?
      }
      $dao->data = $data;
    }

    $dao->save();
  }

}
