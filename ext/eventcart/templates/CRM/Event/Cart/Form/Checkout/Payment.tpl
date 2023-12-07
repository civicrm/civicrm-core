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
    {$line_item.event->title} ({$line_item.event->start_date|crmDate})
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
        <input type="hidden" id="total_amount" value="{$total}">
      </td>
    </tr>
  </tfoot>
</table>
{if $payment_required == true}
  {if !empty($form.is_pay_later.label)}
    <div class="crm-section {$form.is_pay_later.name}-section">
      <div class="label">{$form.is_pay_later.label}</div>
      <div class="content">{$form.is_pay_later.html}
      </div>
      <div class="clear"></div>
    </div>
    <div class="pay-later-instructions" style="display:none">
      {$pay_later_instructions}
    </div>
{/if}
{include file='CRM/Core/BillingBlockWrapper.tpl'}
{/if}
{if $collect_billing_email == true}
<div class="crm-section {$form.billing_contact_email.name}-section">
  <div class="label">{$form.billing_contact_email.label}</div>
  <div class="content">{$form.billing_contact_email.html}
  </div>
  <div class="clear"></div>
</div>
{/if}

<script type="text/javascript">
{if !empty($form.is_pay_later.name)}
var pay_later_sel = "input#{$form.is_pay_later.name}";
{/if}
{literal}
CRM.$(function($) {

  function refresh() {
    {/literal}{if !empty($form.is_pay_later.name)}{literal}
    var is_pay_later = $(pay_later_sel).prop("checked");
    {/literal}{else}
    var is_pay_later = false;
    {/if}{literal}
    $(".credit_card_info-group").toggle(!is_pay_later);
    $(".pay-later-instructions").toggle(is_pay_later);
    $("div.billingNameInfo-section .description").html(is_pay_later ? "Enter the billing address at which you can be invoiced." : "Enter the name as shown on your credit or debit card, and the billing address for this card.");
  }
  {/literal}{if !empty($form.is_pay_later.name)}{literal}
  $(pay_later_sel).change(function() {
    refresh();
  });
  {/literal}{/if}{literal}
  $("input#source").prop('disabled', true);
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
