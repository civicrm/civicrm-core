<?php

namespace Civi\Search;

use Civi\Api4\SearchDisplay;

class EmailSearchDisplays {

  public static function findDisplaysDue(): array {
    $displays = [];
    $emailDisplays = SearchDisplay::get(FALSE)
      ->addWhere('type:name', '=', 'crm-search-display-email-report')
      ->execute();
    foreach ($emailDisplays as $display) {
      if (!array_key_exists('next_run', $display['settings']) || (!empty($display['settings']['next_run']) && ((int) $display['settings']['next_run']) <= time())) {
        $displays[$display['id']] = $display['settings'];
      }
    }
    return $displays;
  }

  /**
   * @throws \DateMalformedStringException
   */
  public static function calculateNextRunDate(array $settings): string {
    $nextRunDate = '';
    // Current date/time
    $now = new \DateTime();
    if (!empty($settings['frequency']) && $settings['frequency'] === 'custom') {
      // TODO: add logic for custom
    }
    elseif (!empty($settings['frquency']) && $settings['frquency'] === 'first') {
      $nextRun = new \DateTime('first day of next month 06:00:00');
      $nextRunDate = $nextRun->getTimestamp();
    }
    elseif (!empty($settings['frequency']) && $settings['frequency'] === 'weekly') {
      $targetWeekday = (int) date('w');
      $currentWeekday = (int) $now->format('w');
      $currentHour = (int) $now->format('H');

      // Determine if the run time has passed today
      $hasPassedToday = ($currentWeekday == $targetWeekday) && ($currentHour >= 6);

      if ($hasPassedToday) {
        // Next occurrence is next week
        $nextRun = new \DateTime("next " . date('l', strtotime("+$targetWeekday days")));
        $nextRun->setTime(6, 0, 0);
      }
      else {
        // Next occurrence is today (if same day) or the upcoming weekday this week
        if ($currentWeekday == $targetWeekday) {
          $nextRun = new \DateTime("today 06:00:00");
        }
        else {
          // Calculate days to add to reach the target weekday this week
          $daysToAdd = ($targetWeekday - $currentWeekday + 7) % 7;
          $nextRun = new \DateTime("+$daysToAdd days 06:00:00");
        }
      }
      $nextRunDate = $nextRun->getTimestamp();
    }
    elseif (!empty($settings['frequency']) && $settings['frequency'] === 'daily') {
      $nextRun = new \DateTime('today 06:00:00');

      // If 06:00 has already passed today, schedule for tomorrow
      if ($now->format('H') >= 6) {
        $nextRun->modify('+1 day');
      }
      $nextRunDate = $nextRun->getTimestamp();
    }
    elseif (!empty($settings['frequency']) && $settings['frequency'] === 'monthly') {
      // Simulate the dynamic cron: '0 6 ' . date('j') . ' * *'
      $targetDay = (int) date('j');
      $hour = 6;
      $minute = 0;

      // Start with the target day in the CURRENT month
      $nextRun = new \DateTime();
      $nextRun->setDate((int) $now->format('Y'), (int) $now->format('n'), $targetDay);
      $nextRun->setTime($hour, $minute, 0);

      // If the target time has passed this month, jump to next month
      // We compare timestamps to handle cases where current day == target day but hour is past
      if ($now->getTimestamp() >= $nextRun->getTimestamp()) {
        $nextRun->modify('first day of next month');
        $nextRun->setDate((int) $nextRun->format('Y'), (int) $nextRun->format('n'), $targetDay);

        // Edge case: If target day is 31 and next month has 30 days,
        // DateTime will overflow to the next month (e.g., March 3).
        // To strictly stay on the last day of short months, you might need extra checks,
        // but standard cron usually skips invalid dates or runs on the last day depending on implementation.
        // For simple "same day number" logic, this overflow is often acceptable or handled by re-checking the day.
        if ((int) $nextRun->format('j') !== $targetDay) {
          // If overflow happened (e.g. asked for 31st in April), force to last day of month or skip?
          // Standard cron behavior for '31' is to SKIP months with <31 days.
          // To mimic "skip", we ensure we are on the correct day.
          // If strict "skip" is needed:
          // Skip this short month entirely
          $nextRun->modify('first day of next month');
          $nextRun->setDate((int) $nextRun->format('Y'), (int) $nextRun->format('n'), $targetDay);
        }
      }

      $nextRunDate = $nextRun->getTimestamp();
    }
    return $nextRunDate;
  }

}
