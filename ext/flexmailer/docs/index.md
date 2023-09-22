FlexMailer (`org.civicrm.flexmailer`) is an email delivery engine for CiviCRM v4.7+.  It replaces the internal guts of CiviMail.  It is a
drop-in replacement which enables *other* extensions to provide richer email features.

By default, FlexMailer supports the same user interfaces, delivery algorithms, and use-cases as CiviMail.  After activating FlexMailer, an
administrator does not need to take any special actions.

The distinguishing improvement here is under-the-hood: it provides better APIs and events for extension-developers.  For example,
other extensions might:

* Change the template language
* Manipulate tracking codes
* Rework the delivery mechanism
* Redefine the batching algorithm
