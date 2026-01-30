<?php
return CRM_Core_CodeGen_BounceType::create('Spam')
  ->addMetadata([
    'description' => ts('Message caught by a content filter'),
    'hold_threshold' => 1,
  ])
  ->addValueTable(['pattern'], [
    ['(bulk( e-?mail)|content|attachment blocking|virus|mail system) filters?'],
    ['(hostile|questionable|unacceptable) content'],
    ['address .+? has not been verified'],

    // FIXME: In the old SQL, the "\" in "\w" didn't get through. We're probably loading the wrong data.
    // ['anti-?spam (polic\w+|software)'],
    ['anti-?spam (policw+|software)'],

    ['anti-?virus gateway has detected'],
    ['blacklisted'],
    ['blocked message'],
    ['content control'],
    ['delivery not authorized'],
    ['does not conform to our e-?mail policy'],
    ['excessive spam content'],
    ['message looks suspicious'],
    ['open relay'],
    ['sender was rejected'],
    ['spam(check| reduction software| filters?)'],
    ['blocked by a user configured filter'],
    ['(detected|rejected) (as|due to) spam'],
    ['X-HmXmrOriginalRecipient'],
    ['Client host .[^ ]*. blocked'],
    ['automatic(ally-generated)? messages are not accepted'],
    ['denied by policy'],
    ['has no corresponding reverse \\(PTR\\) address'],
    ['has a policy that( [^ ]*)? prohibited the mail that you sent'],
    ['is likely unsolicited mail'],
    ['Local Policy Violation'],
    ['ni bilo mogo..?e dostaviti zaradi varnostnega pravilnika'],
    ['abuse report'],
    ['unsolicited mail'],
    ['Complaint via SES'],
  ]);
