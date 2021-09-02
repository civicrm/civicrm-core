{if !empty($customDataType)}
  <div id="customData"></div>
  {*include custom data js file*}
  {include file="CRM/common/customData.tpl"}
  {assign var='cid' value=$cid|default:'false'}
  {literal}
  <script type="text/javascript">
    CRM.$(function($) {
      {/literal}
      {if !empty($customDataSubType)}
        CRM.buildCustomData('{$customDataType}', {$customDataSubType}, false, false, false, false, false, {$cid});
      {else}
        CRM.buildCustomData('{$customDataType}', false, false, false, false, false, false, {$cid});
      {/if}
      {literal}
    });
  </script>
  {/literal}
{/if}
