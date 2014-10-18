{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{* Relationship tab within View Contact - browse, and view relationships for a contact *}
{if !empty($cdType) }
  {include file="CRM/Custom/Form/CustomData.tpl"}
{elseif $action neq 16} {* add, update or view *}
  {include file="CRM/Contact/Form/Relationship.tpl"}
{else}
  <div id="contact-summary-relationship-tab" class="view-content">
    {if $permission EQ 'edit'}
      <div class="action-link">
        <a accesskey="N" href="{crmURL p='civicrm/contact/view/rel' q="cid=`$contactId`&action=add&reset=1"}"
           class="button"><span><div class="icon add-icon"></div>{ts}Add Relationship{/ts}</span></a>
      </div>
    {/if}

    {* display current relationships *}
    <h3>{ts}Current Relationships{/ts}</h3>
    {include file="CRM/Contact/Page/View/RelationshipSelector.tpl" context="current"}
    <div id="permission-legend" class="crm-content-block">
      <span class="crm-marker">* </span>
      {ts}Indicates a permissioned relationship. This contact can be viewed and updated by the other.{/ts}
    </div>

    <div class="spacer"></div>
    <p></p>
    {* display past relationships *}
    <div class="label font-red">{ts}Inactive Relationships{/ts}</div>
    <div class="description">{ts}These relationships are Disabled OR have a past End Date.{/ts}</div>
    {include file="CRM/Contact/Page/View/RelationshipSelector.tpl" context="past"}
  </div>

  {include file="CRM/common/enableDisableApi.tpl"}
  {literal}
  <script type="text/javascript">
    CRM.$(function($) {
      // Changing relationships may affect related members and contributions. Ensure they are refreshed.
      $('#contact-summary-relationship-tab').on('crmPopupFormSuccess', function() {
        CRM.tabHeader.resetTab('tab_contribute');
        CRM.tabHeader.resetTab('tab_member');
      });
    });
  </script>
  {/literal}
{/if} {* close of custom data else*}

