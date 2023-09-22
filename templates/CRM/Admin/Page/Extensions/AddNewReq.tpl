<div class="crm-content-block crm-block">
  {foreach from=$extAddNewReqs item=req}
  <div class="messages status no-popup">
       {icon icon="fa-info-circle"}{/icon}
       {$req.title}<br/>
       {$req.message}
  </div>
  {/foreach}
</div>
