
{if !empty($customDataType)}
  <div id="customData_{$customDataType}" class="crm-customData-block"></div>
  {*include custom data js file*}
  {include file="CRM/common/customData.tpl"}
  {* convert falsey values to NULL so that the default of 'false' works lower down (it's a string not a bool lower down) *}
  {if !$customDataSubType}
    {assign value=null var=customDataSubType}
  {/if}
  {if !$cid}
    {assign value=null var=cid}
  {/if}
  {literal}
  <script type="text/javascript">
    CRM.$(function($) {
      {/literal}
      CRM.buildCustomData('{$customDataType}', {$customDataSubType|default:"false"}, false, false, false, false, false, {$cid|default:"false"});
    {literal}
    });
  </script>
  {/literal}
{/if}
{* jQuery validate *}
{include file="CRM/Form/validate.tpl"}
