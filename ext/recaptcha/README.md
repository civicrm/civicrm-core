# ReCAPTCHA

Core extension to extract the reCAPTCHA functionality from CiviCRM core so it can be disabled/replaced.

The extension is licensed under [AGPL-3.0](LICENSE.txt).


## Using ReCAPTCHA

There is currently no supported method of adding reCAPTCHA to a non CiviCRM core form.

For supported core forms:

This will check the "standard" conditions for adding to a form which are currently:
* Do not add if user is logged in.

It will be added immediately before the last `<div class=crm-submit-buttons>` on the form.

## To do

Note that it may be preferred to actually develop a completely new extension with new logic
for adding one or more methods (such as ReCAPTCHA) for protecting against form abuse.
That would allow this extension to be disabled, removing all references to ReCAPTCHA from
CiviCRM Core and potentially offering much simpler logic that can be fully controlled from an
extension - eg. "Load on all anonymous forms".

#### Develop and document a supported method of adding ReCAPTCHA to your form.
