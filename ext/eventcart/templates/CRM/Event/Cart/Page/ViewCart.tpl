{if $events_count == 0}
  <div class="crm-section crm-eventcart crm-eventcart-noevents">
    <div class="content">
      {ts}You have no events in the cart.{/ts}
    </div>
  </div>
{else}
  <div class="crm-section crm-eventcart crm-eventcart-hasevents">
    <table>
      <thead>
      <tr>
        <th>
        </th>
        <th>
        </th>
      </tr>
      </thead>
      <tbody>
      {foreach from=$events_in_carts item=event_in_cart}
        {if !$event_in_cart.main_conference_event_id}
          <tr>
            <td>
              <a href="{crmURL p='civicrm/event/info' q="reset=1&id=`$event_in_cart.event.id`"}" title="{ts escape='htmlattribute'}View event info page{/ts}" class="bold">{$event_in_cart.event.title}</a>
            </td>
            <td>
              <a title="Remove From Cart" class="action-item" href="{crmURL p='civicrm/event/remove_from_cart' q="reset=1&id=`$event_in_cart.event.id`"}">{ts}Remove{/ts}</a>
            </td>
          </tr>
        {/if}
      {/foreach}
      </tbody>
    </table>
    <a href="{crmURL p='civicrm/event/cart_checkout'}" class="button crm-check-out-button"><i class="crm-i fa-credit-card" role="img" aria-hidden="true"></i> {ts}Checkout{/ts}</a>
  </div>
{/if}
<a href="{crmURL p="civicrm/event/ical" q="reset=1&page=1&html=1"}"><i class="crm-i fa-chevron-left" role="img" aria-hidden="true"></i> Back to Event List</a>
