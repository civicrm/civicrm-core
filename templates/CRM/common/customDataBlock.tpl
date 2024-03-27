
{if !empty($customDataType)}
  <div id="customData"></div>
  {*include custom data js file*}
  {include file="CRM/common/customData.tpl"}
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
