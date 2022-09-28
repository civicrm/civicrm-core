The `RunEvent` (`EVENT_RUN`) fires as soon as FlexMailer begins processing a job.

CiviMail has a recurring task -- `Job.process_mailings` -- which identifies scheduled/pending mailings.  It determines
the `Mailing` and `MailingJob` records, then passes control to `FlexMailer` to perform delivery.  `FlexMailer`
immediately fires the `RunEvent`.

!!! note "`RunEvent` fires for each cron-run."

    By default, FlexMailer uses `DefaultBatcher` which obeys the traditional CiviMail throttling behavior.  This can
    limit the number of deliveries performed within a single cron-run.  If you reach this limit, then it stops
    execution.  However, after 5 or 10 minutes, a new *cron-run* begins.  It passes control to FlexMailer again, and
    then we pick up where we left off.  This means that one `Mailing` and one `MailingJob` could require multiple
    *cron-runs*.

    The `RunEvent` would fire for *every cron run*.

To listen to the `RunEvent`:

```php
<?php
function example_civicrm_container($container) {
  $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
  $container->findDefinition('dispatcher')->addMethodCall('addListener',
    array(\Civi\FlexMailer\FlexMailer::EVENT_RUN, '_example_run')
  );
}

function _example_run(\Civi\FlexMailer\Event\RunEvent $event) {
  printf("Starting work on job #%d for mailing #%d\n", $event->getJob()->id, $event->getMailing()->id);
}

```

!!! note "Stopping the `RunEvent` will stop FlexMailer."

    If you call `$event->stopPropagation()`, this will cause FlexMailer to
    stop its delivery process.
