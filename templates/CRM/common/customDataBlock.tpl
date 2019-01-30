{if $customDataType}
  <div id="customData"></div>
  {*include custom data js file*}
  {include file="CRM/common/customData.tpl"}
  {literal}
  <script type="text/javascript">
    CRM.$(function($) {
      {/literal}
      {if $customDataSubType}
        CRM.buildCustomData('{$customDataType}', {$customDataSubType});
      {else}
        CRM.buildCustomData('{$customDataType}');
      {/if}
      {literal}
    });
  </script>
  {/literal}
{/if}
