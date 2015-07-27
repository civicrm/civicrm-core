{include file="CRM/common/TrackingFields.tpl"}

<table>
  <thead>
    <tr>
      <th class="event-title">
  {ts}Event{/ts}
      </th>
      <th class="participants-column">
  {ts}Participants{/ts}
      </th>
      <th class="cost">
  {ts}Price{/ts}
      </th>
      <th class="amount">
  {ts}Total{/ts}
      </th>
    </tr>
  </thead>
  <tbody>
    {foreach from=$line_items item=line_item}
      <tr class="event-line-item {$line_item.class}">
  <td class="event-title">
    {$line_item.event->title} ({$line_item.event->start_date})
  </td>
  <td class="participants-column">
    {$line_item.num_participants}<br/>
    {if $line_item.num_participants > 0}
      <div class="participants" style="padding-left: 10px;">
        {foreach from=$line_item.participants item=participant}
      {$participant.display_name}<br />
        {/foreach}
      </div>
    {/if}
    {if $line_item.num_waiting_participants > 0}
      {ts}Waitlisted:{/ts}<br/>
      <div class="participants" style="padding-left: 10px;">
        {foreach from=$line_item.waiting_participants item=participant}
      {$participant.display_name}<br />
        {/foreach}
      </div>
    {/if}
  </td>
  <td class="cost">
    {$line_item.cost|crmMoney:$currency|string_format:"%10s"}
  </td>
  <td class="amount">
    &nbsp;{$line_item.amount|crmMoney:$currency|string_format:"%10s"}
  </td>
      </tr>
    {/foreach}
  </tbody>
  <tfoot>
  {if $discounts}
    <tr>
      <td>
      </td>
      <td>
      </td>
      <td>
  {ts}Subtotal:{/ts}
      </td>
      <td>
  &nbsp;{$sub_total|crmMoney:$currency|string_format:"%10s"}
      </td>
    </tr>
  {foreach from=$discounts key=myId item=i}
    <tr>
      <td>{$i.title}
      </td>
      <td>
      </td>
      <td>
      </td>
      <td>
   -{$i.amount|crmMoney:$currency|string_format:"%10s"}
      </td>
    </tr>
   {/foreach}
   {/if}
    <tr>
      <td>
      </td>
      <td>
      </td>
      <td class="total">
  {ts}Total:{/ts}
      </td>
      <td class="total">
  &nbsp;{$total|crmMoney:$currency|string_format:"%10s"}
      </td>
    </tr>
  </tfoot>
</table>
{if $payment_required == true}
<div class="crm-section {$form.is_pay_later.name}-section">
  <div class="label">{$form.is_pay_later.label}</div>
  <div class="content">{$form.is_pay_later.html}
  </div>
  <div class="clear"></div>
</div>
<div class="pay-later-instructions" style="display:none">
  {$pay_later_instructions}
</div>
{include file='CRM/Core/BillingBlock.tpl'}
{/if}
{if $collect_billing_email == true}
<div class="crm-section {$form.billing_contact_email.name}-section">
  <div class="label">{$form.billing_contact_email.label}</div>
  <div class="content">{$form.billing_contact_email.html}
  </div>
  <div class="clear"></div>
</div>
{/if}

{if $administrator}
<!--
<div style="border: 1px solid blue; padding: 5px;">
<b>{ts}Staff use only{/ts}</b>
<div class="crm-section {$form.note.name}-section">
  <div class="label">{$form.note.label}</div>
  <div class="content">{$form.note.html}
    <div class="description">{ts}Note that will be sent to the billing customer.{/ts}</div>
  </div>
  <div class="clear"></div>
</div>
<div class="crm-section {$form.source.name}-section">
  <div class="label">{$form.source.label}</div>
  <div class="content">{$form.source.html}
    <div class="description">{ts}Description of this transaction.{/ts}</div>
  </div>
  <div class="clear"></div>
</div>
<div class="crm-section {$form.payment_type.name}-section">
  <div class="label">{$form.payment_type.label}</div>
  <div class="content">{$form.payment_type.html}
  </div>
  <div class="clear"></div>
</div>
<div class="crm-section {$form.check_number.name}-section" style="display: none;">
  <div class="label">{$form.check_number.label}</div>
  <div class="content">{$form.check_number.html}</div>
  <div class="clear"></div>
</div>
<div class="crm-section {$form.is_pending.name}-section">
  <div class="label">{$form.is_pending.label}</div>
  <div class="content">{$form.is_pending.html}
  </div>
  <div class="clear"></div>
</div>
</div>
-->
{/if}

<script type="text/javascript">
var pay_later_sel = "input#{$form.is_pay_later.name}";
{literal}
CRM.$(function($) {
  function refresh() {
    var is_pay_later = $(pay_later_sel).prop("checked");
    $(".credit_card_info-group").toggle(!is_pay_later);
    $(".pay-later-instructions").toggle(is_pay_later);
    $("div.billingNameInfo-section .description").html(is_pay_later ? "Enter the billing address at which you can be invoiced." : "Enter the name as shown on your credit or debit card, and the billing address for this card.");
  }
  $("input#source").prop('disabled', true);

  $(pay_later_sel).change(function() {
    refresh();
  });
  $(".payment_type-section :radio").change(function() {
    var sel = $(this).attr("id");
    $(".check_number-section").toggle(
        $(this).is(":checked") &&
        $("label[for="+sel+"]").html() == "{/literal}{ts escape='js'}Check{/ts}{literal}"
    );
  });
  refresh();
});
{/literal}
</script>

<div id="crm-submit-buttons" class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{include file="CRM/Event/Cart/Form/viewCartLink.tpl"}
