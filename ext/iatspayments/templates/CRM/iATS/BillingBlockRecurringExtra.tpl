<div id="iats-recurring-start-date">
  <div class="crm-section recurring-start-date">
    <div class="label">{$form.receive_date.label}</div>
    <div class="content">{$form.receive_date.html}
      <div class="description">{ts domain='com.iatspayments.civicrm'}You may select a later start date if you wish.{/ts}</div>
    </div>
    <div class="clear"></div>
  </div>
</div>
{literal}<script type="text/javascript">cj(function ($) { iatsRecurStartRefresh();});</script>{/literal}
