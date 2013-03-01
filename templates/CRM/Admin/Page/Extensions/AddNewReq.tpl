<div class="crm-content-block crm-block">
  {foreach from=$extAddNewReqs item=req}
  <div class="messages status no-popup">
       <div class="icon inform-icon"></div>
       {$req.title}<br/>
       {$req.message}
  </div>
  {/foreach}
</div>
