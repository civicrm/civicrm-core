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

use CRM_Search_ExtensionUtil as E;

/**
 * Search Schedule BAO
 */
class CRM_Search_BAO_SearchSchedule extends CRM_Search_DAO_SearchSchedule implements \Civi\Core\HookInterface {

  /**
   * Event fired before creating or editing a SearchSchedule.
   *
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event): void {
    $params = &$event->params;

    if (in_array($event->action, ['create', 'edit'], TRUE)) {
      // If next_run is explicitly provided, preserve it
      if (array_key_exists('next_run', $params)) {
        return;
      }

      $schedule = $params;
      if ($event->action === 'edit' && $event->id) {
        $existing = \Civi\Api4\SearchSchedule::get(FALSE)
          ->addWhere('id', '=', $event->id)
          ->execute()
          ->first();
        if ($existing) {
          $timingChanged = array_key_exists('start_date', $params)
            || array_key_exists('frequency_unit', $params)
            || array_key_exists('frequency_interval', $params)
            || array_key_exists('is_active', $params);
          if (!$timingChanged && !empty($existing['next_run'])) {
            return;
          }
          $schedule = array_merge($existing, $params);
        }
      }

      $nextRun = self::calculateNextRun($schedule);
      $params['next_run'] = $nextRun ?? '';
    }
  }

  /**
   * Calculates the next datetime a schedule should be sent.
   *
   * @param array $schedule
   *   Record array containing start_date, frequency_unit, frequency_interval, and optional is_active.
   * @param \DateTimeInterface|null $after
   *   The point in time the next run must fall strictly after (defaults to 'now').
   * @return string|null
   *   Formatted 'Y-m-d H:i:s' or NULL if schedule is inactive or missing timing fields.
   */
  public static function calculateNextRun(array $schedule, ?\DateTimeInterface $after = NULL): ?string {
    if (empty($schedule['start_date']) || empty($schedule['frequency_unit'])) {
      return NULL;
    }
    if (isset($schedule['is_active']) && !$schedule['is_active']) {
      return NULL;
    }

    $interval = max(1, (int) ($schedule['frequency_interval'] ?? 1));
    $unit = $schedule['frequency_unit'];
    $startDate = new \DateTime($schedule['start_date']);
    $afterDate = $after ? clone $after : new \DateTime();

    // If start_date hasn't occurred yet, that is the next run
    if ($startDate > $afterDate) {
      return $startDate->format('Y-m-d H:i:s');
    }

    $h = (int) $startDate->format('H');
    $m = (int) $startDate->format('i');
    $s = (int) $startDate->format('s');

    switch ($unit) {
      case 'day':
        $diffDays = (int) $startDate->diff($afterDate)->format('%r%a');
        $steps = (int) floor($diffDays / $interval);
        $candidate = (clone $startDate)->modify("+" . ($steps * $interval) . " days");
        while ($candidate <= $afterDate) {
          $candidate->modify("+$interval days");
        }
        return $candidate->format('Y-m-d H:i:s');

      case 'week':
        $daysPerStep = $interval * 7;
        $diffDays = (int) $startDate->diff($afterDate)->format('%r%a');
        $steps = (int) floor($diffDays / $daysPerStep);
        $candidate = (clone $startDate)->modify("+" . ($steps * $daysPerStep) . " days");
        while ($candidate <= $afterDate) {
          $candidate->modify("+$daysPerStep days");
        }
        return $candidate->format('Y-m-d H:i:s');

      case 'month':
        $targetDay = (int) $startDate->format('j');
        $yearsDiff = (int) $afterDate->format('Y') - (int) $startDate->format('Y');
        $monthsDiff = $yearsDiff * 12 + ((int) $afterDate->format('n') - (int) $startDate->format('n'));
        $steps = max(0, (int) floor($monthsDiff / $interval));

        $startYear = (int) $startDate->format('Y');
        $startMonth = (int) $startDate->format('n');

        do {
          $totalMonths = $steps * $interval;
          $monthOffset = $startMonth - 1 + $totalMonths;
          $year = $startYear + intdiv($monthOffset, 12);
          $month = ($monthOffset % 12) + 1;
          $daysInMonth = (int) (new \DateTime(sprintf('%04d-%02d-01', $year, $month)))->format('t');
          $day = min($targetDay, $daysInMonth);
          $candidate = (new \DateTime())->setDate($year, $month, $day)->setTime($h, $m, $s);
          $steps++;
        } while ($candidate <= $afterDate);

        return $candidate->format('Y-m-d H:i:s');

      case 'year':
        $targetMonth = (int) $startDate->format('n');
        $targetDay = (int) $startDate->format('j');
        $yearsDiff = (int) $afterDate->format('Y') - (int) $startDate->format('Y');
        $steps = max(0, (int) floor($yearsDiff / $interval));
        $startYear = (int) $startDate->format('Y');

        do {
          $year = $startYear + ($steps * $interval);
          $daysInMonth = (int) (new \DateTime(sprintf('%04d-%02d-01', $year, $targetMonth)))->format('t');
          $day = min($targetDay, $daysInMonth);
          $candidate = (new \DateTime())->setDate($year, $targetMonth, $day)->setTime($h, $m, $s);
          $steps++;
        } while ($candidate <= $afterDate);

        return $candidate->format('Y-m-d H:i:s');

      default:
        return NULL;
    }
  }

  /**
   * Pseudoconstant options for the `format` field.
   *
   * @return array
   */
  public static function getFormats(): array {
    return [
      'csv' => E::ts('CSV'),
      'xlsx' => E::ts('Excel'),
      'ods' => E::ts('OpenOffice'),
      'pdf' => E::ts('PDF'),
    ];
  }

  /**
   * Pseudoconstant options for the `frequency_unit` field.
   *
   * Labels are pluralized according to `frequency_interval` when available.
   *
   * @param string|null $fieldName
   * @param array $params
   * @return array
   */
  public static function getFrequencyUnits(?string $fieldName = NULL, array $params = []): array {
    $count = $params['values']['frequency_interval'] ?? 1;
    return array_intersect_key(
      CRM_Core_SelectValues::getRecurringFrequencyUnits($count),
      array_flip(['day', 'week', 'month', 'year'])
    );
  }

}
