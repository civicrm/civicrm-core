{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{* CiviCampaign DashBoard (launch page) *}

{* build the campaign selector *}
{if $subPageType eq 'campaign'}

  {* load the campaign search and selector here *}
  {include file="CRM/Campaign/Form/Search/Campaign.tpl"}

{* build the survey selector *}
{elseif $subPageType eq 'survey'}

  {* load the survey search and selector here *}
  {include file="CRM/Campaign/Form/Search/Survey.tpl"}

{* build normal page *}
{elseif $subPageType eq 'petition'}

  {* load the petition search and selector here *}
  {include file="CRM/Campaign/Form/Search/Petition.tpl"}

{* build normal page *}
{else}

   {* enclosed all tabs and its content in a block *}
   <div class="crm-block crm-content-block crm-campaign-page">

   <div id="mainTabContainer" class="ui-tabs ui-widget ui-widget-content ui-corner-all">
     <ul class="crm-campaign-tabs-list">
       {foreach from=$allTabs key=tabName item=tabValue}
         <li id="tab_{$tabValue.id}" class="crm-tab-button ui-corner-bottom">
            <a href="{$tabValue.url}" title="{$tabValue.title}"><span></span>{$tabValue.title}</a>
         </li>
       {/foreach}
     </ul>
  </div>


{literal}
<script type="text/javascript">

//explicitly stop spinner
function stopSpinner( ) {
  cj('li.crm-tab-button').each(function(){ cj(this).find('span').text(' ');})
}

cj(document).ready( function( ) {
     {/literal}
     var spinnerImage = '<img src="{$config->resourceBase}i/loading.gif" style="width:10px;height:10px"/>';
     {literal}

     var selectedTabIndex = {/literal}{$selectedTabIndex}{literal};
     cj("#mainTabContainer").tabs( {
                                    selected: selectedTabIndex,
                                    spinner: spinnerImage,
            cache: true,
            load: stopSpinner
            });
});

</script>
{/literal}
<div class="clear"></div>
</div> {* crm-content-block ends here *}
{/if}




