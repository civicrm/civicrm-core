<?php

namespace Civi\Search;

use Civi\Api4\SearchDisplay;

class EmailSearchDisplays {

  public static function findDisplaysDue(): array {
    $displays = [];
    $emailDisplays = SearchDisplay::get(FALSE)
      ->addWhere('type:name', '=', 'crm-search-display-email-report')
      ->execute();
    $now = time();
    foreach ($emailDisplays as $display) {
      $settings = $display['settings'];
      // Use next_run if set, otherwise treat startDate as the first scheduled run
      $dueAt = !empty($settings['next_run'])
        ? (int) $settings['next_run']
        : (!empty($settings['startDate']) ? strtotime($settings['startDate']) : NULL);
      if ($dueAt !== NULL && $dueAt <= $now) {
        $displays[$display['id']] = $settings;
      }
    }
    return $displays;
  }

  /**
   * @throws \DateMalformedStringException
   */
  public static function calculateNextRunDate(array $settings): int|string {
    if (empty($settings['startDate'])) {
      return '';
    }

    $startDate = new \DateTime($settings['startDate']);
    $now = new \DateTime();

    // If startDate hasn't happened yet, that's the next run regardless of frequency
    if ($startDate > $now) {
      return $startDate->getTimestamp();
    }

    $frequency = $settings['frequency'] ?? '';

    // Pull the time-of-day off startDate for reuse
    $h = (int) $startDate->format('H');
    $m = (int) $startDate->format('i');
    $s = (int) $startDate->format('s');

    switch ($frequency) {
      case 'daily':
        $nextRun = (clone $now)->setTime($h, $m, $s);
        if ($nextRun <= $now) {
          $nextRun->modify('+1 day');
        }
        return $nextRun->getTimestamp();

      case 'weekly':
        // Match startDate's weekday + time
        $targetWeekday = (int) $startDate->format('w');
        $currentWeekday = (int) $now->format('w');
        $daysToAdd = ($targetWeekday - $currentWeekday + 7) % 7;
        $nextRun = (clone $now)->modify("+$daysToAdd days")->setTime($h, $m, $s);
        if ($nextRun <= $now) {
          $nextRun->modify('+1 week');
        }
        return $nextRun->getTimestamp();

      case 'monthly':
        // Match startDate's day-of-month + time, clamping for short months
        $targetDay = (int) $startDate->format('j');
        $nextRun = clone $now;
        $daysInMonth = (int) $nextRun->format('t');
        $nextRun->setDate(
          (int) $now->format('Y'),
          (int) $now->format('n'),
          min($targetDay, $daysInMonth)
        );
        $nextRun->setTime($h, $m, $s);
        if ($nextRun <= $now) {
          $nextRun->modify('first day of next month');
          $daysInMonth = (int) $nextRun->format('t');
          $nextRun->setDate(
            (int) $nextRun->format('Y'),
            (int) $nextRun->format('n'),
            min($targetDay, $daysInMonth)
          );
          $nextRun->setTime($h, $m, $s);
        }
        return $nextRun->getTimestamp();

      case 'first':
        // 1st of next month at startDate's time
        $nextRun = new \DateTime('first day of next month');
        $nextRun->setTime($h, $m, $s);
        return $nextRun->getTimestamp();

      case 'custom':
        if (empty($settings['frequency_custom'])) {
          return '';
        }
        try {
          $next = self::nextCronMatch($settings['frequency_custom'], $now);
          return $next->getTimestamp();
        }
        catch (\Throwable $e) {
          \Civi::log()->warning('Invalid cron expression on email report: ' . $e->getMessage());
          return '';
        }

      default:
        return '';
    }
  }

  /**
   * Parse one cron field (e.g. "*", "0,30", "9-17", '*\/15", "1-5") into the
   * list of integers it matches within [$min, $max].
   */
  private static function parseCronField(string $field, int $min, int $max): array {
    $values = [];
    foreach (explode(',', $field) as $part) {
      $step = 1;
      if (str_contains($part, '/')) {
        [$range, $stepStr] = explode('/', $part, 2);
        $step = max(1, (int) $stepStr);
      }
      else {
        $range = $part;
      }
      if ($range === '*') {
        $start = $min;
        $end = $max;
      }
      elseif (str_contains($range, '-')) {
        [$s, $e] = explode('-', $range, 2);
        $start = (int) $s;
        $end = (int) $e;
      }
      else {
        $start = $end = (int) $range;
      }
      if ($start < $min || $end > $max || $start > $end) {
        throw new \InvalidArgumentException("Cron field out of range: $field");
      }
      for ($i = $start; $i <= $end; $i += $step) {
        $values[] = $i;
      }
    }
    return array_values(array_unique($values));
  }

  /**
   * Find the next datetime strictly after $after that matches a 5-field
   * cron expression: "minute hour day-of-month month day-of-week".
   */
  private static function nextCronMatch(string $expr, \DateTime $after): \DateTime {
    $parts = preg_split('/\s+/', trim($expr));
    if (count($parts) !== 5) {
      throw new \InvalidArgumentException("Cron expression must have 5 fields: $expr");
    }
    $minutes  = self::parseCronField($parts[0], 0, 59);
    $hours    = self::parseCronField($parts[1], 0, 23);
    $days     = self::parseCronField($parts[2], 1, 31);
    $months   = self::parseCronField($parts[3], 1, 12);
    $weekdays = self::parseCronField($parts[4], 0, 6);

    // Cron's "OR" rule: when both DOM and DOW are restricted, a day matches
    // if EITHER field matches. When only one is restricted, only that one matters.
    $domAll = (count($days) === 31);
    $dowAll = (count($weekdays) === 7);

    // Start at the next whole minute after $after
    $candidate = clone $after;
    $candidate->setTime((int) $candidate->format('H'), (int) $candidate->format('i'), 0);
    $candidate->modify('+1 minute');

    // Safety cap: ~4 years of jump-ahead steps is enough for "Feb 29 only" etc.
    for ($i = 0; $i < 200000; $i++) {
      $mon = (int) $candidate->format('n');
      if (!in_array($mon, $months, TRUE)) {
        $candidate->modify('first day of next month')->setTime(0, 0, 0);
        continue;
      }
      $dom = (int) $candidate->format('j');
      $dow = (int) $candidate->format('w');
      if ($domAll && $dowAll) {
        $dayOk = TRUE;
      }
      elseif ($domAll) {
        $dayOk = in_array($dow, $weekdays, TRUE);
      }
      elseif ($dowAll) {
        $dayOk = in_array($dom, $days, TRUE);
      }
      else {
        $dayOk = in_array($dom, $days, TRUE) || in_array($dow, $weekdays, TRUE);
      }
      if (!$dayOk) {
        $candidate->modify('+1 day')->setTime(0, 0, 0);
        continue;
      }
      $hour = (int) $candidate->format('G');
      if (!in_array($hour, $hours, TRUE)) {
        $candidate->modify('+1 hour')->setTime((int) $candidate->format('G'), 0, 0);
        continue;
      }
      $minute = (int) $candidate->format('i');
      if (!in_array($minute, $minutes, TRUE)) {
        $candidate->modify('+1 minute');
        continue;
      }
      return $candidate;
    }
    throw new \RuntimeException("No cron match within bounds for: $expr");
  }

}
