<?php
namespace Civi\ActionSchedule;

class Events {

  /**
   * @see \Civi\ActionSchedule\Event\MappingRegisterEvent
   * @deprecated - You may simply use the event name directly. dev/core#1744
   */
  const MAPPINGS = 'civi.actionSchedule.getMappings';

  /**
   * @see \Civi\ActionSchedule\Event\MailingQueryEvent
   * @deprecated - You may simply use the event name directly. dev/core#1744
   */
  const MAILING_QUERY = 'civi.actionSchedule.prepareMailingQuery';

}
