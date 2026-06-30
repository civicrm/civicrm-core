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
namespace Civi\Api4\Generic;

/**
 * Base class for all api v4 scheduled jobs.
 *
 * This allows for extensions to create scheduled job actions without needing to specify the runInNonProductionEnvironmentJob command themselves
 *
 * @method bool getRunInNonProductionEnvironment()
 * @method $this setRunInNonProductionEnvironment(bool $runInNonProductionEnvironment) Specify if this job should be run in NonProducitonEnvironments or not
 */
abstract class AbstractScheduledJob extends AbstractAction {

  /**
   * Should this Job be run in Non production Environments
   *
   * @var bool
   */
  protected $runInNonProductionEnvironment = FALSE;

}
