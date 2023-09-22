{* DEPRECATED script, should be refactored out and removed *}
<script type='text/javascript'>
  var selectedTab = '{$defaultTab}';
  var tabContainer = '#mainTabContainer';
  {if $tabContainer}tabContainer = '{$tabContainer}';{/if}
  {if $selectedChild}selectedTab = '{$selectedChild}';{/if}
  {literal}
  CRM.$(function($) {
    var tabIndex = $('#tab_' + selectedTab).prevAll().length;
    $(tabContainer).tabs( {active: tabIndex} );
  });
  {/literal}
</script>
