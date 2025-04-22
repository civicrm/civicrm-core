<div class="pull-right float-right crm-eventcart-eventinfo-buttons">
  <div class="action-link align-right crm-eventcart-eventinfo-addtocart"></div>
  <div class="action-link align-right crm-eventcart-eventinfo-viewcart"><a href="{crmURL p='civicrm/event/view_cart'}" class="button crm-shoppingcart-button"><i class="crm-i fa-shopping-cart" aria-hidden="true"></i> {ts}View Cart{/ts}</a></div>
  {if $eventcart_has_events}
    <div class="action-link align-right crm-eventcart-eventinfo-checkout"><a href="{crmURL p='civicrm/event/cart_checkout'}" class="button crm-check-out-button"><i class="crm-i fa-credit-card" aria-hidden="true"></i> {ts}Checkout{/ts}</a></div>
  {/if}
</div>
{literal}
<script>
  CRM.$('.register_link-top > a').prependTo('.crm-eventcart-eventinfo-addtocart');
  CRM.$('.register_link-top').hide();
</script>
{/literal}
