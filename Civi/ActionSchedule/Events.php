<?php
namespace Civi\ActionSchedule;

class Events {

  /**
   * Register any available mappings.
   *
   * @see EntityListEvent
   */
  const MAPPINGS = 'civi.actionSchedule.getMappings';

  /**
   * Prepare the pre-mailing query. This query loads details about
   * the contact/entity so that they're available for mail-merge.
   */
  const MAILING_QUERY = 'civi.actionSchedule.prepareMailingQuery';

}
