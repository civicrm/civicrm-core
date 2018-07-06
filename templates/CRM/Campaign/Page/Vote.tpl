{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
     <div class="icon inform-icon"></div>
     {ts}You are not authorized to access this page.{/ts}
 </div>
{/if}
