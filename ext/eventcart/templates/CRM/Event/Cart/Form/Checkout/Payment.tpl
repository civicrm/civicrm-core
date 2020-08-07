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
        <input type="hidden" id="total_amount" name="total_amount" value="{$total}">
      </td>
    </tr>
  </tfoot>
</table>

{if $form.first_name}
  <div class="crm-section {$form.first_name.name}-section">
    <div class="label">{$form.first_name.label}</div>
    <div class="content">{$form.first_name.html}
    </div>
    <div class="clear"></div>
  </div>
{/if}
{if $form.last_name}
  <div class="crm-section {$form.last_name.name}-section">
    <div class="label">{$form.last_name.label}</div>
    <div class="content">{$form.last_name.html}
    </div>
    <div class="clear"></div>
  </div>
{/if}
{if $form.email}
  <div class="crm-section {$form.email.name}-section">
    <div class="label">{$form.email.label}</div>
    <div class="content">{$form.email.html}
    </div>
    <div class="clear"></div>
  </div>
{/if}

{if $payment_required}
  {if $form.payment_processor_id.label}
    {* PP selection only works with JS enabled, so we hide it initially *}
    <fieldset class="crm-public-form-item crm-group payment_options-group" style="display:none;">
      <legend>{ts}Payment Options{/ts}</legend>
      <div class="crm-section payment_processor-section">
        <div class="label">{$form.payment_processor_id.label}</div>
        <div class="content">{$form.payment_processor_id.html}</div>
        <div class="clear"></div>
      </div>
    </fieldset>
  {/if}
  {include file='CRM/Core/BillingBlockWrapper.tpl'}
{/if}

{if $isCaptcha}
  {include file='CRM/common/ReCAPTCHA.tpl'}
{/if}

<div id="crm-submit-buttons" class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{include file="CRM/Event/Cart/Form/viewCartLink.tpl"}
{include file="CRM/Form/validate.tpl"}
