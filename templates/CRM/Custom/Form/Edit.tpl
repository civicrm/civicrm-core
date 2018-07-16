{* Edit custom data on Edit entity forms *}
{* Requires <div id="customData"></div> on the form *}
{*include custom data js file*}
{include file="CRM/common/customData.tpl"}
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    {/literal}
    CRM.buildCustomData( '{$customDataType}' );
    {if $customDataSubType}
    CRM.buildCustomData( '{$customDataType}', {$customDataSubType} );
    {/if}
    {literal}
  });
</script>
{/literal}