# Postbox
This extension provides a new way to send messages from CiviCRM. It may be particularly useful for generating automated messages, e.g. with a CiviRules action.

It owes a lot to the Email Api3 extension, though is updated to Api4.

Key aims:
- leverage Api4 to provide a familiar interface to developers, and be usable anywhere Api4 is
- provide observability with Api4 (create a SearchKit to see emails in the queue, and emails that have been sent)
- send asynchronously, dont block e.g. user form submission with the actual sending - just put it in a queue to send as soon as possible

At the moment the first type of message we support is an EmailMessage.

You can queue an email by creating a new EmailMessage:

```

\Civi\Api4\EmailMessage::create(FALSE)
  ->addValue('to_contact_id', $contactId)
  ->addValue('subject', 'Test Email')
  ->addValue('body', 'Test content')
  ->addValue('from_site_email_address_id', 3)
  ->execute();

```

You can send a specific message with the Send action:

```
\Civi\Api4\EmailMessage::send(FALSE)
  ->addWhere('id', '=', $messageId)
  ->execute();
```

But you generally shouldn't generally need to do that because unsent messages will be sent automatically by a shutdown worker.


## Thoughts

- There's nothing really email specific about using the shutdown worker to process the queue - this should be generalisable, and could be specified when creating the queue.
- Previously we enabled setting arbitrary From Name / Email address. This has been restricted for now for security considerations
  - If using arbitrary from_email from_name it will be sensitive to whether this an allowed sender from the sites SMTP gateway. How could we validate this? (Wildcard SiteEmailAddress records?)
- Send is currently always automated, as soon as possible. Maybe some messages have lower priority or should be deferred.
- Send is currently one-time per message. Maybe sometimes you want to force resending a message that was already sent (though maybe then you should copy the original message record, to maintain the log?)
- It would be nice to send messages other than email. But that introduces complexity:
  - The shape of message content is different. It would be good if we could translate (ie to send an sms use our html=>text processor and put the subject line at the top, if provided). If you know you want to send SMS you shouldnt put too much clever HTML in the body..)
  - The message recipient options are different. Contact IDs are good for this. But CC/BCC are email specific.
  - Message channel might be a two-way negotiation: Alice want to send Bob an email. But Bob has said they want to receive all their messages by Signal.
  - Maybe you want to receive messages by email AND in-app notification. A single entity works well for multi-channel sending.
- It would be nice to specify more recipients in more ways. But that introduces lots of complexity.
- It could be nice to send messages with MessageTemplates and Tokens. But we already have lots of codepaths for that.
- If you send templated messages, do you want to save the final rendered content of every message to the database? Maybe sometimes you do, sometimes you don't. Maybe this could be a setting (site-wide, per email, per message template?).
