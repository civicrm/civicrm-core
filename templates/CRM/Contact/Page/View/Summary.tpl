{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Contact Summary template for new tabbed interface. Replaces Basic.tpl *}
{if $action eq 2}
  {include file="CRM/Contact/Form/Contact.tpl"}
{else}

  <div class="crm-summary-contactname-block crm-inline-edit-container">
    <div class="crm-summary-block" id="contactname-block">
      {include file="CRM/Contact/Page/Inline/ContactName.tpl"}
    </div>
  </div>

  {if !$summaryPrint}
    <div class="crm-actions-ribbon">
      <ul id="actions">
        {crmRegion name="contact-actions-ribbon"}
        {assign var='urlParams' value="reset=1"}
        {if !empty($searchKey)}
          {assign var='urlParams' value=$urlParams|cat:"&key=$searchKey"}
        {/if}
        {if $context}
          {assign var='urlParams' value=$urlParams|cat:"&context=$context"}
        {/if}

        {* CRM-12735 - Conditionally include the Actions and Edit buttons if contact is NOT in trash.*}
        {if !$isDeleted}
          {crmPermission has='access CiviCRM'}
            <li class="crm-contact-activity crm-summary-block">
              {include file="CRM/Contact/Page/Inline/Actions.tpl"}
            </li>
          {/crmPermission}
          {* Include Edit button if contact has 'edit contacts' permission OR user is viewing their own contact AND has 'edit my contact' permission. CRM_Contact_Page_View::checkUserPermission handles this and assigns $permission true as needed. *}
          {if $permission EQ 'edit'}
            <li>
              {crmButton p='civicrm/contact/add' q="$urlParams&action=update&cid=$contactId" class="edit"}
                {ts}Edit{/ts}
              {/crmButton}
            </li>
          {/if}
        {/if}

        {* Check for permissions to provide Restore and Delete Permanently buttons for contacts that are in the trash. *}
        {crmPermission has='access deleted contacts'}
          {if $is_deleted}
            <li class="crm-contact-restore">
              {crmButton p='civicrm/contact/view/delete' q="reset=1&cid=$contactId&restore=1" class="delete" icon="undo"}
                {ts}Restore from Trash{/ts}
              {/crmButton}
            </li>

            {crmPermission has='delete contacts'}
              <li class="crm-contact-permanently-delete">
                {crmButton p='civicrm/contact/view/delete' q="reset=1&delete=1&cid=$contactId&skip_undelete=1" class="delete" icon="trash"}
                  {ts}Delete Permanently{/ts}
                {/crmButton}
              </li>
            {/crmPermission}
          {/if}
        {/crmPermission}

        {* Previous and Next contact navigation when accessing contact summary from search results. *}
        {if $nextPrevError}
          <li class="crm-next-action">
            {help id="id-next-prev-buttons"}&nbsp;
          </li>
        {else}
          {if $nextContactID}
            <li class="crm-next-action">
              {crmButton p='civicrm/contact/view' q="$urlParams&cid=$nextContactID" class="view" title=$nextContactName icon="chevron-right"}
                {ts}Next{/ts}
              {/crmButton}
            </li>
          {/if}
          {if $prevContactID}
            <li class="crm-previous-action">
              {crmButton p='civicrm/contact/view' q="$urlParams&cid=$prevContactID" class="view" title=$prevContactName icon="chevron-left"}
                {ts}Previous{/ts}
              {/crmButton}
            </li>
          {/if}
        {/if}

        {if $groupOrganizationUrl}
          <li class="crm-contact-associated-groups">
            {crmButton href=$groupOrganizationUrl class="associated-groups" icon="cubes"}
              {ts}Associated Multi-Org Group{/ts}
            {/crmButton}
          </li>
        {/if}
        {/crmRegion}
      </ul>
      <div class="clear"></div>
    </div><!-- .crm-actions-ribbon -->
  {/if}

  {include file='CRM/common/TabHeader.tpl' tabHeader=$allTabs containerClasses="crm-contact-page crm-inline-edit-container" listClasses="crm-contact-tabs-list" tabIdPrefix="contact-"}

{/if}

{* CRM-10560 *}
{literal}
<script type="text/javascript">
CRM.$(function($) {
  $('.crm-inline-edit-container').crmFormContactLock({
    ignoreLabel: "{/literal}{ts escape='js'}Ignore{/ts}{literal}",
    saveAnywayLabel: "{/literal}{ts escape='js'}Save Anyway{/ts}{literal}",
    reloadLabel: "{/literal}{ts escape='js'}Reload Page{/ts}{literal}"
  });
});
</script>
{/literal}
