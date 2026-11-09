FlexMailer (`org.civicrm.flexmailer`) is CiviMail's email delivery engine. It ships with CiviCRM and is always enabled. It provides
events which enable *other* extensions to provide richer email features.

The distinguishing improvement here is under-the-hood: it provides better APIs and events for extension-developers.  For example,
other extensions might:

* Change the template language
* Manipulate tracking codes
* Rework the delivery mechanism
* Redefine the batching algorithm
