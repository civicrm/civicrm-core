{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Voting Tab Interface - Easy way to get the voter Interview. *}
{if $subPageType eq 'interview'}
   {* build the voter interview grid here *}
   {include file='CRM/Campaign/Form/Task/Interview.tpl'}
{elseif $subPageType eq 'reserve'}
   {* build the ajax search and voters reserve interface here *}
   {include file='CRM/Campaign/Form/Gotv.tpl'}
{elseif $tabHeader}
  {include file="CRM/common/TabHeader.tpl"}
  <script type="text/javascript">
    {* very crude refresh of tabs - fixme: use datatable native refresh method *}
    {literal}
    CRM.$(function($) {
      $('#mainTabContainer').on('tabsbeforeactivate', function(e, ui) {
        // fixme - can't search more than once! Uncomment this code, switching tabs gives qfkey error.
        //if (ui.newTab.is('#tab_reserve')) {
          //$('.searchVoter.button').click();
          ui.oldPanel.data('civiCrmSnippet') && ui.oldPanel.crmSnippet('destroy');
        //}
      });
    });
    {/literal}
  </script>

{else}
 <div class="messages status no-popup">
     {icon icon="fa-info-circle"}{/icon}
     {ts}You are not authorized to access this page.{/ts}
 </div>
{/if}
