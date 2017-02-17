{include file="CRM/common/TrackingFields.tpl"}

<div class="crm-block crm-event-thankyou-form-block">
  <p>
    {ts}This is your receipt of payment made for the following event registration.{/ts}
  </p>
  <p>
    {ts 1=$transaction_id 2=$transaction_date|date_format:"%D %I:%M %p %Z"}Your order number is <strong>#%1</strong>. Please print this confirmation for your records. You will receieve a confirmation email with the information below.  Information about the workshops will be sent separately to each participant. Here's a summary of your transaction placed on %2:{/ts}
  </p>
  {if $pay_later_receipt && $is_pay_later}
  <p>
  {$pay_later_receipt}
  </p>
  {/if}
  {if $payment_required}
    <div class="crm-group billing_name_address-group">
      <div class="header-dark">
  {ts}Billing Name and Address{/ts}
      </div>
      <div class="crm-section no-label billing_name-section">
    <div class="content">{$billing_name}</div>
    <div class="clear"></div>
      </div>
      <div class="crm-section no-label billing_address-section">
    <div class="content">
      {$billing_street_address}<br/>
      {$billing_city}, {$billing_state} {$billing_postal_code}
    </div>
    <div class="clear"></div>
      </div>
    </div>
    {if $credit_card_type}
      <div class="crm-group credit_card-group">
        <div class="header-dark">
                  {ts}Credit Card Information{/ts}
        </div>
        <div class="crm-section no-label credit_card_details-section">
                  <div class="content">{$credit_card_type}</div>
                  <div class="content">{$credit_card_number}</div>
                  <div class="content">{ts}Expires{/ts}: {$credit_card_exp_date.M}/{$credit_card_exp_date.Y}
                    <div class="clear"></div>
                  </div>
        </div>
      </div>
    {/if}
  {/if}
  <table>
    <thead>
      <tr style="border-bottom: 1px solid #ccc">
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
    <td class="event-info">
      <div class="event-title"><strong>{$line_item.event->title}</strong></div>
      {if $line_item.event->is_show_location}
        <div class="event-location">
      {if $line_item.location.address.1.name}
        {$line_item.location.address.1.name}
      {/if}
      {if $line_item.location.address.1.street_address}
        {$line_item.location.address.1.street_address}
      {/if}
      {if $line_item.location.address.1.supplemental_address_1}
        {$line_item.location.address.1.supplemental_address_1}
      {/if}
      {if $line_item.location.address.1.supplemental_address_2}
        {$line_item.location.address.1.supplemental_address_2}
      {/if}
      {if $line_item.location.address.1.supplemental_address_3}
        {$line_item.location.address.1.supplemental_address_3}
      {/if}
      {if $line_item.location.address.1.city}
        {$line_item.location.address.1.city}, {$line_item.location.address.1.state_province} {$line_item.location.address.1.postal_code}
      {/if}
        </div>
      {/if}{*End of isShowLocation condition*}
      <div class="event-data">
        {$line_item.event->start_date|date_format:"%D %I:%M %p"} -
        {$line_item.event->end_date|date_format:"%I:%M %p"}
      </div>
    </td>
    <td class="participants-column">
      {$line_item.num_participants}<br />
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
      {ts}Subtotal{/ts}:
    </td>
    <td>
      &nbsp;{$sub_total|crmMoney:$currency|string_format:"%10s"}
    </td>
  </tr>
  {foreach from=$discounts key=myId item=i}
    <tr>
      <td>
        {$i.title}
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
    <strong>{ts}Total{/ts}:</strong>
  </td>
  <td class="total">
    <strong>&nbsp;{$total|crmMoney:$currency|string_format:"%10s"}</strong>
  </td>
      </tr>
    </tfoot>
  </table>
  <p>{ts}If you have questions about the status of your registration or purchase please contact us.{/ts}</p>
</div>
