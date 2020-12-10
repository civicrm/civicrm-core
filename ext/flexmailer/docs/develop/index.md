## Unit tests

The [headless unit tests](https://docs.civicrm.org/dev/en/latest/testing/#headless) are based on PHPUnit and `cv`. Simply run:

```
$ phpunit5
```

## Events

!!! tip "Symfony Events"

    This documentation references the [Symfony EventDispatcher](http://symfony.com/components/EventDispatcher).
    If this is unfamiliar, you can read [a general introduction to Symfony events](http://symfony.com/doc/2.7/components/event_dispatcher.html)
    or [a specific introduction about CiviCRM and Symfony events](https://docs.civicrm.org/dev/en/latest/hooks/setup/symfony/).

FlexMailer is an *event* based delivery system. It defines a few events:

* [CheckSendableEvent](CheckSendableEvent.md): In this event, one examines a draft mailing to determine if it is complete enough to deliver.
* [RunEvent](RunEvent.md): When a cron-worker starts processing a `MailingJob`, this event fires. It can be used to initialize resources... or to completely circumvent the normal process.
* [WalkBatchesEvent](WalkBatchesEvent.md): In this event, one examines the recipient list and pulls out a subset for whom you want to send email.
* [ComposeBatchEvent](ComposeBatchEvent.md): In this event, one examines the mail content and the list of recipients -- then composes a batch of fully-formed email messages.
* [SendBatchEvent](SendBatchEvent.md): In this event, one takes a batch of fully-formed email messages and delivers the messages.

These events are not conceived in the same way as a typical *CiviCRM hook*; rather, they resemble *pipelines*.  For each event, several listeners
have an opportunity to weigh-in, and the *order* of the listeners is important.  As such, it helps to *inspect* the list of listeners.  You can do
this with the CLI command, `cv`:

```
$ cv debug:event-dispatcher /flexmail/
[Event] civi.flexmailer.checkSendable
+-------+------------------------------------------------------------+
| Order | Callable                                                   |
+-------+------------------------------------------------------------+
| #1    | Civi\FlexMailer\Listener\Abdicator->onCheckSendable()      |
| #2    | Civi\FlexMailer\Listener\RequiredFields->onCheckSendable() |
| #3    | Civi\FlexMailer\Listener\RequiredTokens->onCheckSendable() |
+-------+------------------------------------------------------------+

[Event] civi.flexmailer.walk
+-------+---------------------------------------------------+
| Order | Callable                                          |
+-------+---------------------------------------------------+
| #1    | Civi\FlexMailer\Listener\DefaultBatcher->onWalk() |
+-------+---------------------------------------------------+

[Event] civi.flexmailer.compose
+-------+-------------------------------------------------------+
| Order | Callable                                              |
+-------+-------------------------------------------------------+
| #1    | Civi\FlexMailer\Listener\BasicHeaders->onCompose()    |
| #2    | Civi\FlexMailer\Listener\ToHeader->onCompose()        |
| #3    | Civi\FlexMailer\Listener\BounceTracker->onCompose()   |
| #4    | Civi\FlexMailer\Listener\DefaultComposer->onCompose() |
| #5    | Civi\FlexMailer\Listener\Attachments->onCompose()     |
| #6    | Civi\FlexMailer\Listener\OpenTracker->onCompose()     |
| #7    | Civi\FlexMailer\Listener\HookAdapter->onCompose()     |
+-------+-------------------------------------------------------+

[Event] civi.flexmailer.send
+-------+--------------------------------------------------+
| Order | Callable                                         |
+-------+--------------------------------------------------+
| #1    | Civi\FlexMailer\Listener\DefaultSender->onSend() |
+-------+--------------------------------------------------+
```

The above listing shows the default set of listeners at time of writing. (Run the command yourself to see how they appear on your system.)
The default listeners behave in basically the same way as CiviMail's traditional BAO-based delivery system (respecting `mailerJobSize`,
`mailThrottleTime`, `mailing_backend`, `hook_civicrm_alterMailParams`, etal).

There are a few tricks for manipulating the pipeline:

* __Register new listeners__. Each event has its own documentation which describes how to do this.
* __Manage the priority__. When registering a listener, the `addListener()` function accepts a `$priority` integer. Use this to move up or down the pipeline.

    !!! note "Priority vs Order"

        When writing code, you will set the *priority* of a listener. The default is `0`, and the usual range is `2000` (first) to `-2000` (last).

        <!-- The default listeners have priorities based on the constants `FlexMailer::WEIGHT_PREPARE` (1000), `FlexMailer::WEIGHT_MAIN` (0),
        `FlexMailer::WEIGHT_ALTER` (-1000), and `FlexMailer::WEIGHT_END` (-2000). -->

        At runtime, the `EventDispatcher` will take all the listeners and sort them by priority. This produces the *order*, which simply counts up (`1`, `2`, `3`, ...).

* __Alter a listener__. Most listeners are *services*, and you can manipulate options on these services. For example, suppose you wanted to replace the default bounce-tracking mechanism.
  Here's a simple way to disable the default `BounceTracker`:

    ```php
    <?php
    \Civi::service('civi_flexmailer_bounce_tracker')->setActive(FALSE);
    ```

    Of course, this change needs to be made before the listener runs. You might use a global hook (like `hook_civicrm_config`), or you might
    have your own listener which disables `civi_flexmailer_bounce_tracker` and adds its own bounce-tracking.

    Most FlexMailer services support `setActive()`, which enables you to completely replace them.

    Additionally, some services have their own richer methods. In this example, we modify the list of required tokens:

    ```php
    <?php
    $tokens = \Civi::service('civi_flexmailer_required_tokens')
      ->getRequiredTokens();

    unset($tokens['domain.address']);

    \Civi::service('civi_flexmailer_required_tokens')
      ->setRequiredTokens($tokens);
    ```

## Services

Most features in FlexMailer are implemented by *services*, and you can override or manipulate these features if you understand the corresponding service.
For more detailed information about how to manipulate a service, consult its docblocks.

* Listener services (`CheckSendableEvent`)
     * `civi_flexmailer_required_fields` (`RequiredFields.php`): Check for fields like "Subject" and "From".
     * `civi_flexmailer_required_tokens` (`RequiredTokens.php`): Check for tokens like `{action.unsubscribeUrl}` (in `traditional` mailings).
* Listener services (`WalkBatchesEvent`)
     * `civi_flexmailer_default_batcher` (`DefaultBatcher.php`): Split the recipient list into smaller batches (per CiviMail settings)
* Listener services (`ComposeBatchEvent`)
     * `civi_flexmailer_basic_headers` (`BasicHeaders.php`): Add `From:`, `Reply-To:`, etc
     * `civi_flexmailer_to_header` (`ToHeader.php`): Add `To:` header
     * `civi_flexmailer_bounce_tracker` (`BounceTracker.php`): Add bounce-tracking codes
     * `civi_flexmailer_default_composer` (`DefaultComposer.php`): Read the email template and evaluate any tokens (based on CiviMail tokens)
     * `civi_flexmailer_attachments` (`Attachments.php`): Add attachments
     * `civi_flexmailer_open_tracker` (`OpenTracker.php`): Add open-tracking codes
     * `civi_flexmailer_test_prefix` (`TestPrefix.php`): Add a prefix to any test mailings
     * `civi_flexmailer_hooks` (`HookAdapter.php`): Backward compatibility with `hook_civicrm_alterMailParams`
* Listener services (`SendBatchEvent`)
     * `civi_flexmailer_default_sender` (`DefaultSender.php`): Send the batch using CiviCRM's default delivery service
* Other services
     * `civi_flexmailer_html_click_tracker` (`HtmlClickTracker.php`): Add click-tracking codes (for HTML messages)
     * `civi_flexmailer_text_click_tracker` (`TextClickTracker.php`): Add click-tracking codes (for plain-text messages)
     * `civi_flexmailer_api_overrides` (`Services.php.php`): Alter the `Mailing` APIs
