<?php
namespace Civi\ActionSchedule;

class Events {

  /**
   * Register any available mappings.
   *
   * @see EntityListEvent
   */
  const MAPPINGS = 'actionSchedule.getMappings';

  const MAILING_QUERY = 'actionSchedule.prepareMailingQuery';

}
