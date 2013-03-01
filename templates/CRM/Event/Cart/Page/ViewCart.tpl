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
    <a href="{crmURL p='civicrm/event/info' q="reset=1&id=`$event_in_cart.event.id`"}" title="{ts}View event info page{/ts}" class="bold">{$event_in_cart.event.title}</a>
  </td>
  <td>
    <a title="Remove From Cart" class="action-item" href="{crmURL p='civicrm/event/remove_from_cart' q="reset=1&id=`$event_in_cart.event.id`"}">{ts}Remove{/ts}</a>
  </td>
      </tr>
     {/if}
    {/foreach}
  </tbody>
</table>
{if $events_count > 0}
        <a href="{crmURL p='civicrm/event/cart_checkout' }" title="Check Out" class="button crm-check-out-button"><span>{ts}Check Out{/ts}</span></a>
<br /><br />
{/if}
<a href="{crmURL p="civicrm/event/ical" q="reset=1&page=1&html=1"}">&laquo; Back to Event List</a>
