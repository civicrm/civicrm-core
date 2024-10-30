{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding event  *}
{capture assign="adminPriceSets"}{crmURL p='civicrm/admin/price' q="reset=1"}{/capture}
    {if !$paymentProcessor}
        {capture assign=ppUrl}{crmURL p='civicrm/admin/paymentProcessor' q="reset=1"}{/capture}
        <div class="status message">
                {ts 1=$ppUrl}No Payment Processor has been configured / enabled for your site. If this is a <strong>paid event</strong> AND you want users to be able to <strong>register and pay online</strong>, you will need to <a href='%1'>configure a Payment Processor</a> first. Then return to this screen and assign the processor to this event.{/ts} {docURL page="user/contributions/payment-processors"}
                <p>{ts}NOTE: Alternatively, you can enable the <strong>Pay Later</strong> option below without setting up a payment processor. All users will then be asked to submit payment offline (e.g. mail in a check, call in a credit card, etc.).{/ts}</p>
        </div>
    {/if}
<div class="crm-block crm-form-block crm-event-manage-fee-form-block">
    <table class="form-layout">
       <tr class="crm-event-manage-fee-form-block-is_monetary">
          <td class="label">{$form.is_monetary.label}</td>
          <td>{$form.is_monetary.html}</td>
       </tr>
    </table>

    <div id="event-fees">
        <table id="currency" class="form-layout">
             <tr class='crm-event-manage-fee-form-block-currency'>
                <td class="label">{$form.currency.label}</td>
          <td>{$form.currency.html}</td>
             </tr>
        </table>
        {if $paymentProcessor}
         <table id="paymentProcessor" class="form-layout">
             <tr class="crm-event-manage-fee-form-block-payment_processor">
                <td class="label">{$form.payment_processor.label} {help id="id-payment_processor"}</td>
              <td>{$form.payment_processor.html}</td>
             </tr>
         </table>
        {/if}

        <table id="payLater" class="form-layout">
            <tr class="crm-event-manage-fee-form-block-is_pay_later">
               <td class="label">{$form.is_pay_later.label}</td>
               <td>{$form.is_pay_later.html}
                  <span class="description">{ts}Check this box if you want to give users the option to submit payment offline (e.g. mail in a check, call in a credit card, etc.).{/ts}</span>
              </td>
            </tr>
            <tr>
              <td>&nbsp;</td>
              <td>
                <table id="payLaterOptions" class="form-layout">
                    <tr class="crm-event-manage-fee-form-block-pay_later_text">
                       <td class="label">{$form.pay_later_text.label}
                         <span class="crm-marker"> *</span> {help id="id-pay-later-text"}</td>
                       <td>{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='pay_later_text' id=$id}{/if}
                         {$form.pay_later_text.html|crmAddClass:big}
                       </td>
                    </tr>
                    <tr class="crm-event-manage-fee-form-block-pay_later_receipt">
                       <td class="label">{$form.pay_later_receipt.label}<span class="crm-marker"> *</span> </td>
                       <td>{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='pay_later_receipt' id=$id}{/if}
                         {$form.pay_later_receipt.html|crmAddClass:big}
                       </td>
                    </tr>
                    <tr>
                       <td class="label">{$form.is_billing_required.label} {help id="id-is_billing_required"}</td>
                       <td>{$form.is_billing_required.html}</td>
                    </tr>
                </table>
              </td>
            </tr>
        </table>

        <table id="contributionType" class="form-layout">
            <tr class="crm-event-manage-fee-form-block-fee_label">
               <td class="label">{$form.fee_label.label}<span class="crm-marker"> *</span>
               </td>
               <td>{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='fee_label' id=$id}{/if}{$form.fee_label.html}
               </td>
            </tr>
            <tr class="crm-event-manage-fee-form-block-financial_type_id">
               <td class="label">{$form.financial_type_id.label}<span class="crm-marker"> *</span> {help id="id-financial_type_id"}</td>
               <td>{$form.financial_type_id.html}</td>
            </tr>
        </table>

      <table id="priceSet" class="form-layout">
            <tr class="crm-event-manage-fee-form-block-price_set_id">
               <td class="label">{$form.price_set_id.label}</td>
               <td>{$form.price_set_id.html}
                 <div class="description">{ts}Select a Price Set to offer multiple individually priced options for event registrants. Otherwise, leave this empty and enter fixed fee levels below.{/ts}</div></td>
            </tr>
      </table>

        <div id="map-field" >
        <fieldset id="map-field"><legend>{ts}Regular Fees{/ts}</legend>
        <span class="description">{ts}Use the table below to enter descriptive labels and amounts for up to ten event fee levels. These will be presented as a list of radio button options. Both the label and dollar amount will be displayed. You can also configure one or more sets of discounted fees by checking "Discounts by Signup Date" below.{/ts}
        <br />
        {if $isQuick}
          {ts}Click <a id='quickconfig' href='#'>here</a> if you want to configure the Regular Fees below as part of a Price Set, with the added flexibility and complexity that entails.{/ts}
        {/if}
        </span>
        <table id="map-field-table">
        <tr class="columnheader"><td scope="column">{ts}Fee Label{/ts}</td><td scope="column">{ts}Amount{/ts}</td><td scope="column">{ts}Default?{/ts}<br />{$form.default.0.html}</td></tr>
        {section name=loop start=1 loop=11}
           {assign var=idx value=$smarty.section.loop.index}
           <tr><td class="even-row crm-event-manage-fee-form-block-label_{$idx}">{$form.label.$idx.html}</td><td class="crm-event-manage-fee-form-block-value_{$idx}">{$form.value.$idx.html}</td><td class="even-row crm-event-manage-fee-form-block-default_{$idx}">{$form.default.$idx.html}</td></tr>
        {/section}
        </table>
        </fieldset>

    <div id="isDiscount">
         <table class="form-layout">
             <tr class="crm-event-manage-fee-form-block-is_discount">
                <td class="label">{$form.is_discount.label}</td>
                <td>{$form.is_discount.html} {help id="id-is-discount"}</td>
             </tr>
         </table>
    </div>
    <div class="spacer"></div>
    <div>
        <fieldset id="discount">
  <table>
  <tr class="columnheader">
        <td>&nbsp;</th>
        <td>{ts}Discount Set{/ts}</td>
        <td>{ts}Start Date{/ts}</td>
        <td>{ts}End Date{/ts}</td>
    </tr>

  {section name=rowLoop start=1 loop=6}
     {assign var=index value=$smarty.section.rowLoop.index}
     <tr id="discount_{$index}" class=" crm-event-manage-fee-form-block-discount_{$index} {if $index GT 1 AND empty( $form.discount_name[$index].value)} hiddenElement {/if} form-item {cycle values="odd-row,even-row"}">
           <td>{if $index GT 1} <a onclick="showHideDiscountRow('discount_{$index}', false, {$index}); return false;" name="discount_{$index}" href="#" class="form-link">{icon icon="fa-trash"}{ts}remove discount set{/ts}{/icon}</span></a>{/if}
           </td>
           <td class="crm-event-manage-fee-form-block-discount_name"> {$form.discount_name.$index.html}</td>
           <td class="crm-event-manage-fee-form-block-discount_start_date"> {$form.discount_start_date.$index.html} </td>
           <td class="crm-event-manage-fee-form-block-discount_end_date"> {$form.discount_end_date.$index.html} </td>
     </tr>
    {/section}
    </table>
        <div id="discountLink" class="add-remove-link">
           <a onclick="showHideDiscountRow( 'discount', true);return false;" id="discountLink" href="#" class="form-link"><i class="crm-i fa-plus action-icon" aria-hidden="true"></i> {ts}another discount set{/ts}</a>
        </div>
        {$form._qf_Fee_submit.html}

        {if $discountSection}
            <fieldset id="map-field"><legend>{ts}Discounted Fees{/ts}</legend>
      <table id="map-field-table">
            <tr class="columnheader">
         <td scope="column">{ts}Fee Label{/ts}</td>
         {section name=dloop start=1 loop=6}
            {assign var=i value=$smarty.section.dloop.index}
      {if $form.discount_name.$i.value}
            <td scope="column">{$form.discount_name.$i.value}</td>
      {/if}
         {/section}
         <td scope="column">{ts}Default?{/ts}</td>
      </tr>

            {section name=loop start=1 loop=11}
               {assign var=idx value=$smarty.section.loop.index}
               <tr><td class="even-row">{$form.discounted_label.$idx.html}</td>
            {section name=loop1 start=1 loop=6}
                     {assign var=idy value=$smarty.section.loop1.index}
          {if $form.discount_name.$idy.value}
                <td>{$form.discounted_value.$idx.$idy.html}</td>
          {/if}
            {/section}
            <td class="even-row">{$form.discounted_default.$idx.html}</td>
         </tr>
            {/section}
            </table>
            </fieldset>
            {if $discountSection eq 2}
                <script type="text/javascript">
                {literal}
                    CRM.$(function($) {
                        $('#discounted_label_1').focus( );
                    });
                {/literal}
                </script>
            {/if}
        {/if}
        </fieldset>
    </div>
    </div>
    </div>
<div class="crm-submit-buttons">
   {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
</div>

{include file="CRM/common/deferredFinancialType.tpl" context='Event'}
{include file="CRM/common/showHide.tpl"}
<script type="text/javascript">
    {if $price}
    {literal}
    // Re-show Fee Level grid if Price Set select has been set to none.
    if (cj('#price_set_id').val() == '') {
       cj('#map-field').show();
    }
    {/literal}
    {/if}
    {literal}

    if ( document.getElementsByName('is_monetary')[0].checked ) {
        cj('#event-fees').show();
    }

    function warnDiscountDel( ) {
        if ( ! document.getElementsByName('is_discount')[0].checked ) {
            CRM.alert('{/literal}{ts escape="js"}If you uncheck "Discounts by Signup Date" and Save this form, any existing discount sets will be deleted.{/ts} {ts escape="js"}This action cannot be undone.{/ts} {ts escape="js"}If this is NOT what you want to do, you can check "Discounts by Signup Date" again.{/ts}', '{ts escape="js"}Warning{/ts}{literal}', 'alert', {expires: 0});
        }
    }

    /**
     * Function used to show /hide discount and set defaults
     */
    function showHideDiscountRow( rowName, show, index ) {
        if ( show ) {
            // show first hidden element and set date default
            var counter = 0;
            cj('tr[id^=' + rowName + ']').each( function( i ) {
                counter++;
                if ( cj(this).css('display') == 'none' ) {
                    cj(this).show( );

                    // set default
                    var currentRowId = cj(this).attr('id');
                    var temp = currentRowId.split('_');
                    var currentElementID = temp[1];
                    var lastElementID    = currentElementID - 1 ;

                    var lastEndDate = cj( '#discount_end_date_' + lastElementID ).datepicker( 'getDate' );
                    if ( lastEndDate ) {
                        var discountDate = new Date( Date.parse( lastEndDate ) );
                        discountDate.setDate( discountDate.getDate() + 1 );
                        var newDate = discountDate.toDateString();
                        newDate = new Date( Date.parse( newDate ) );
                        cj( '#discount_start_date_' + currentElementID ).datepicker('setDate', newDate );
                    }

                    if ( counter == 5 ) {
                        cj('#discountLink').hide( );
                    }
                    return false;
                }
            });
        } else {
            // hide tr and clear dates
            cj( '#discount_end_date_' + index ).val('');
            cj( '#discount_name_' + index ).val('');
            cj( '#discount_start_date_' + index ).val('');
            cj( '#' + rowName ).hide( );
            cj('#discountLink').show( );
        }
    }

{/literal}
</script>


{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="is_pay_later"
    trigger_value       =""
    target_element_id   ="payLaterOptions"
    target_element_type ="block"
    field_type          ="radio"
    invert              = 0
}
{if $price }
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="price_set_id"
    trigger_value       =""
    target_element_id   ="map-field"
    target_element_type ="block"
    field_type          ="select"
    invert              = 0
}
{/if}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="is_discount"
    trigger_value       =""
    target_element_id   ="discount"
    target_element_type ="block"
    field_type          ="radio"
    invert              = 0
}

{if $isQuick}
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    $("#quickconfig").click(function(e) {
      e.preventDefault();
      CRM.confirm({
        width: 400,
        message: {/literal}"{ts escape='js'}Once you switch to using a Price Set, you won't be able to switch back to your existing settings below except by re-entering them. Are you sure you want to switch to a Price Set?{/ts}"{literal}
      }).on('crmConfirm:yes', function() {
          {/literal}
          var dataUrl  = '{crmURL p="civicrm/ajax/rest" h=0 q="className=CRM_Core_Page_AJAX&fnName=setIsQuickConfig&context=civicrm_event&id=$eventId"}';
          {literal}
        $.getJSON(dataUrl).done(function(result) {window.location = CRM.url("civicrm/admin/price/field", {reset: 1, action: 'browse', sid: result});});
        });
      });
    });
</script>
{/literal}
{/if}
