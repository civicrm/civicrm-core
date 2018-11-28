com.iatspayments.civicrm
===============

CiviCRM Extension for iATS Web Services Payment Processor - Date: Oct 17, 2017. 
Version 1.5.3 for 4.6.x and below.
Version 1.6.1 for 4.7.x.

This README.md contains information specific to system administrators/developers. Information for users/implementors can be found in the Documentation Wiki: https://github.com/iATSPayments/com.iatspayments.civicrm/wiki/Documentation

Requirements
------------

1. CiviCRM 4.6.x or 4.7.x. We strongly recommend that you keep up with the most recent version of each branch.

2. Your PHP needs to include the SOAP extension (php.net/manual/en/soap.setup.php), recommended that you use at least PHP 5.6 but 5.3 and above should work if it supports TLS1.1/1.2 and SHA-256.

3. To use this extension in production, You must have an iATS Payments Account - and have configured it to accept payment though WebServices. You can use the shared iATS test account credentials for initial setup and testing. For details please see the Documentation Wiki: https://github.com/iATSPayments/com.iatspayments.civicrm/wiki/Documentation

4. To handle ACH/EFT Contributions (verification of them) and to handle Recurring Contributions (of any type) you must configure cron for your CiviCRM install. Information about how to do this can be found in: http://wiki.civicrm.org/confluence/display/CRMDOC/Managing+Scheduled+Jobs


Installation
------------

This extension follows the standard installation method - if you've got a supported CiviCRM version and you've set up your extensions directory, it'll appear in the Manage Extensions list as 'iATS Payments (com.iatspayments.civicrm)'. Hit Install.

As of CiviCRM 5.x, the iATS extension is distributed with the CiviCRM download. This is generally the right version to install. See https://github.com/iATSPayments/com.iatspayments.civicrm/issues/242 for notes on converting from a previous manual install.

If you need help with installing extensions, try: https://wiki.civicrm.org/confluence/display/CRMDOC/Extensions

If you want to try out a particular version directly from github, you probably already know how to do that.

Once the extension is installed, you need to add the payment processor(s) and input your iATS credentials:

1. Administer -> System Settings -> Payment Processors -> + Add Payment Processor

2. Select iATS Payments Credit Card, iATS Payments ACH/EFT, or iATS Payments SWIPE, they are all provided by this extension (the instructions differ only slightly for each one). You can create multiple payment processor entries using the same credentials for the different types.

3. The "Name" of the payment processor is what your site visitors will see when they select a payment method, so typically use "Credit Card" here, or "Credit Card C$" (or US$) if there's any doubt about the currency. Your iATS Payments Account is configured for a single currency, so when you set up the payment page, you'll have to manually ensure you set the right currency (not an issue if you're only handling one currency).

4. The test account uses Agent Code = TEST88 and Password = TEST88. This is a shared test account, so don't put in any private information.

5. If you'd like to test using live workflows, you can just temporarily use the test account credentials in your live processor fields.

6. Create a Contribution Page (or go to an existing one) -> Under Configure -> Contribution Amounts -> select your newly installed/configured Payment Processor(s), and Save.

Extension Testing Notes
-----------------------

1. Our test matrix includes 21 type of transactions at the moment. View a summary of the results here: https://cloud.githubusercontent.com/assets/5340555/5616064/2459a9b8-94be-11e4-84c7-2ef0c83cc744.png

2. Manage Contribution Pages -> Links -> Live Page.

  * iATS Payments Credit Card: use test VISA: 4222222222222220 security code = 123 and any future Expiration date - to process any $amount.

  * iATS Payments ACH/EFT: use 000000 for the Transit Number; 123 for the Bank Number; 123456 for the Bank Account Number $1

  * iATS Payments SWIPE: not easy to test - even if you have an Encrypted USB Card Reader (sourced by iATS Payments) you will need a physical fake credit card with: 4222222222222220 security code = 123 and any future Expiration date in the magnetic strip - to process any $amount.

  * iATS Payments UK Direct Debit: use 12345678 for Account Number; 000000 for Sort Code

7. iATS has another test VISA: 41111111111111111 security code = 123 and any future Expiration date

8. Reponses for a transaction with VISA: 41111111111111111 depend on the $amount processed - as follows
  * 1.00 OK: 678594;
  * 2.00 REJ: 15;
  * 3.00 OK: 678594;
  * 4.00 REJ: 15;
  * 5.00 REJ: 15;
  * 6.00 OK: 678594:X;
  * 7.00 OK: 678594:y;
  * 8.00 OK: 678594:A;
  * 9.00 OK: 678594:Z;
  * 10.00 OK: 678594:N;
  * 15.00, if CVV2=1234 OK: 678594:Y; if there is no CVV2: REJ: 19
  * 16.00 REJ: 2;
  * Other Amount REJ: 15

9. After completing a TEST payment -> check the Contributions -> Dashboard. Credit Card Transactions are authorized (=Completed) right away. ACH/EFT + UK direct Debit will be (=Pending).

10. Visit https://home.iatspayments.com -> and click the Client Login button (top right)
  * Login with TEST88 and TEST88
  * hit Reports -> Journal - Credit Card Transactions -> Get Journal -> if it has been a busy day there will be lots of transactions here - so hit display all and scroll down to see the transaction you just processed via CiviCRM.
  * hit Reports -> Journal - ACHEFT Transactions -> List Batches (the test transaction will be here until it is sent to the bank for processing - after that - and depending on the Result - it will appear in either the ACHEFT Approval or the ACHEFT Reject journal.

11. For iATS Payments UK Direct Debit -> visit: https://www.uk.iatspayments.com
  * Login with UDDD88 and DDTESTUK
  * hit Virtual Terminal -> Customer Database -> Search by Name -> hit Edit icon (on the left) -> to see all details, including the Reference Number (which should match up with what you saw on your Thank you screen in CiviCRM). 
  * NOTE: Each charity needs to have a BACS accredited supplier vet their CiviCRM Direct Debit - Contribution Pages

12. If things don't look right, you can turn on Drupal and CiviCRM logging - try another TEST transaction - and then see some detailed logging of the SOAP exchanges for more hints about where it might have gone wrong.

13. To test recurring contributions - try creating a recurring contribution for every day and then go back the next day and manually trigger Scheduled Job: iATS Payments Recurring Contributions

14. To test ACH/EFT contributions - manually run Scheduled Job: iATS Payments Verification - it will check with iATS to see if there is any word from the bank yet. How long it takes before a yeah or neah is available depends on the day of the week and the time the transaction is submitted. It can take overnight (over weekend) to get a verification. 

Once you're happy all is well - then all you need to do is update the Payment Processor data - with your own iATS' Agent Code and Password.

Remember that iATS master accounts (ending in 01) can typically NOT be used to push monies into via web services. So when setting up your Account with iATS - ask them to create another (set of) Agent Codes for you: e.g. 80 or 90, etc.

Also remember to turn off debugging/logging on any production environment!

Issues
------

The best source for understanding current issues with the most recent release is the github issue queue:
https://github.com/iATSPayments/com.iatspayments.civicrm/issues

Some issues may be related to core CiviCRM issues, and may not have an immediate solution, but we'll endeavour to help you understand, work-around, and/or fix whatever concerns you raise on the issue queue.

Below is a list of some of the most common issues:

'Backend' ACH/EFT is not supported by CiviCRM core. Having an enabled ACH/EFT payment processor broke the backend live credit card payment page in core (until it was fixed here https://issues.civicrm.org/jira/browse/CRM-14442), so this module fixes that if it's an issue, and also provides links to easily allow administrators to input ACH/EFT on behalf of constituents. A similar problem existings for backend membership and event payments, and this has only been fixed in core for 4.6.

9002 Error - if you get this when trying to make a contribution, then you're getting that error back from the iATS server due to an account misconfiguration. One source is due to some special characters in your passwd.

CiviCRM core assigns Membership status (=new) and extends Membership End date as well as Event status (=registered) as soon as ACH/EFT is submitted (so while payment is still pending - this could be several days for ACH/EFT). If the contribution receives a Ok:BankAccept -> the extension will mark the contribution in CiviCRM as completed. If the contribution does NOT receive a Ok:BankAccept -> the extension will mark the contribution in CiviCRM as rejected - however - associated existing Membership and Event records may need to be updated manually.

For 4.6, recurring ACH/EFT memberships contributions are automatically approved by CiviCRM due to a bug in CiviCRM Core, but which should be fixed in the near future.

Please note that ACH Returns require manually processing. iATS Payments will notify an organization by Email in case such ACH Returns occur - the reason (e.g. NSF) is included. It is up to CiviCRM administrators to handle this in CiviCRM according to your organization's procedures (e.g. if these were monies re: Event registration -> should that registration be canceled as well or will you ask participant to bring cash; if NSF fees should be charged to the participant etc).

Caution on the use of Pricesets in recurring contributions. This extension will try to use the original transactions' line items. But there are two separate issues here. First, CiviCRM API does an incomplete job with the bookkeeping of line items, so if you need detailed bookkeeping of line items in recurring contributions, you may be disappointed. Separately, if the total amount of the recurring contribution has changed, then there's no machine way of reliably re-allocating it into the original line items, so in that case, they are not used at all. Though not always ideal, a workaround might be to do different transactions for different types of CiviCRM payments instead.

Please post an issue to the github repository if you have any questions.
