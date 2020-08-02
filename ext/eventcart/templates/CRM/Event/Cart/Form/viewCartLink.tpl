{capture assign=cartURL}{crmURL p='civicrm/event/view_cart' q="reset=1"}{/capture}
<div>
{ts 1=$cartURL}<a href='%1'>Return to Cart</a>{/ts}
</div>
