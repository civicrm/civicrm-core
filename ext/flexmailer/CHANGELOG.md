# Change Log

## v0.2-alpha1

* Override core's `Mailing.preview` API to support rendering via
  Flexmailer events.
* (BC Break) In the class `DefaultComposer`, change the signature for
  `createMessageTemplates()` and `applyClickTracking()` to provide full
  access to the event context (`$e`).
