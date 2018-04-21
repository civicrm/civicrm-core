{*
 iATS direct debit UK customization
 Extra fields in Thank You Screen
*}
<div>
  <div class="display-block">
Bank Address: {$form.payer_validate_address.html}<br />
Service User Number: {$form.payer_validate_service_user_number.html}<br />
Reference: {$form.payer_validate_reference.html}<br />
Start Date: {$form.payer_validate_start_date.html}
Today's Date: {$form.payer_validate_date.html}
  </div>
  <h3>Direct Debit Guarantee</h3>
  <ul>
    <li>The Guarantee is offered by all banks and building societies that accept instructions to pay Direct Debits</li>
    <li>If there are any changes to the amount, date or frequency of your Direct Debit the organisation will notify you (normally 10 working days) in advance of your account being debited or as otherwise agreed. If you request the organisation to collect a payment, confirmation of the amount and date will be given to you at the time of the request</li>
    <li>If an error is made in the payment of your Direct Debit, by the organisation or your bank or building society, you are entitled to a full and immediate refund of the amount paid from your bank or building society</li>
    <li>If you receive a refund you are not entitled to, you must pay it back when the organisation asks you to</li>
    <li>You can cancel a Direct Debit at any time by simply contacting your bank or building society. Written confirmation may be required. Please also notify the organisation</li>
  </ul>
  <br/>
  <div class="messages status continue_instructions-section">
    <p>Please print this page for your records.</p>
    <div id="printer-friendly">
      <a title="Print this page." onclick="window.print(); return false;" href="#">
        <div class="ui-icon ui-icon-print"></div>
      </a>
    </div>
    <div class="clear"></div>
  </div>
  <br/>
  <div class="clear"></div>
    <div>
      <img width=166 height=61 src="{crmResURL ext=com.iatspayments.civicrm file=templates/CRM/iATS/bacs.png}">
      <img width=148 height=57 src="{crmResURL ext=com.iatspayments.civicrm file=templates/CRM/iATS/direct-debit.jpg}">
      <img width=134 height=55 src="{crmResURL ext=com.iatspayments.civicrm file=templates/CRM/iATS/iats.jpg}">
    </div>
</div>
