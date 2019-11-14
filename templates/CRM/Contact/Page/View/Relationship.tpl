{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Relationship tab within View Contact - browse, and view relationships for a contact *}
{if $action neq 16} {* add, update or view *}
  {include file="CRM/Contact/Form/Relationship.tpl"}
{else}
  <div id="contact-summary-relationship-tab" class="view-content">
    {if $permission EQ 'edit'}
      <div class="action-link">
        {crmButton accesskey="N"  p='civicrm/contact/view/rel' q="cid=`$contactId`&action=add&reset=1" icon="plus-circle"}{ts}Add Relationship{/ts}{/crmButton}
      </div>
    {/if}

    {* display current relationships *}
    <h3>{ts}Current Relationships{/ts}</h3>
    <div id="permission-legend" class="help">
      <span class="crm-label">Permissioned Relationships: </span>
      {include file="CRM/Contact/Page/View/RelationshipPerm.tpl" permType=1 afterText=true}
    </div>
    {include file="CRM/Contact/Page/View/RelationshipSelector.tpl" context="current"}

    <div class="spacer"></div>
    {* display past relationships *}
    <h3 class="font-red">{ts}Inactive Relationships{/ts}</h3>
    <div class="help">{ts}These relationships are Disabled OR have a past End Date.{/ts}</div>
    {include file="CRM/Contact/Page/View/RelationshipSelector.tpl" context="past"}
  </div>

  {include file="CRM/common/enableDisableApi.tpl"}
  {literal}
  <script type="text/javascript">
    CRM.$(function($) {
      // Changing relationships may affect related members and contributions. Ensure they are refreshed.
      $('#contact-summary-relationship-tab').on('crmPopupFormSuccess', function() {
        CRM.tabHeader.resetTab('#tab_contribute');
        CRM.tabHeader.resetTab('#tab_member');
      });
    });
  </script>
  {/literal}
{/if} {* close of custom data else*}
