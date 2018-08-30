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
        {assign var='urlParams' value="reset=1"}
        {if $searchKey}
          {assign var='urlParams' value=$urlParams|cat:"&key=$searchKey"}
        {/if}
        {if $context}
          {assign var='urlParams' value=$urlParams|cat:"&context=$context"}
        {/if}

        {* CRM-12735 - Conditionally include the Actions and Edit buttons if contact is NOT in trash.*}
        {if !$isDeleted}
          {if call_user_func(array('CRM_Core_Permission','check'), 'access CiviCRM')}
            <li class="crm-contact-activity crm-summary-block">
              {include file="CRM/Contact/Page/Inline/Actions.tpl"}
            </li>
          {/if}
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
        {if call_user_func(array('CRM_Core_Permission','check'), 'access deleted contacts') and $is_deleted}
          <li class="crm-contact-restore">
            {crmButton p='civicrm/contact/view/delete' q="reset=1&cid=$contactId&restore=1" class="delete" icon="undo"}
              {ts}Restore from Trash{/ts}
            {/crmButton}
          </li>

          {if call_user_func(array('CRM_Core_Permission','check'), 'delete contacts')}
            <li class="crm-contact-permanently-delete">
              {crmButton p='civicrm/contact/view/delete' q="reset=1&delete=1&cid=$contactId&skip_undelete=1" class="delete" icon="trash"}
                {ts}Delete Permanently{/ts}
              {/crmButton}
            </li>
          {/if}

        {elseif call_user_func(array('CRM_Core_Permission','check'), 'delete contacts')}
          <li class="crm-delete-action crm-contact-delete">
            {crmButton p='civicrm/contact/view/delete' q="&reset=1&delete=1&cid=$contactId" class="delete" icon="trash"}
              {ts}Delete Contact{/ts}
            {/crmButton}
          </li>
        {/if}

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

        {if !empty($groupOrganizationUrl)}
          <li class="crm-contact-associated-groups">
            {crmButton href=$groupOrganizationUrl class="associated-groups" icon="cubes"}
              {ts}Associated Multi-Org Group{/ts}
            {/crmButton}
          </li>
        {/if}
      </ul>
      <div class="clear"></div>
    </div><!-- .crm-actions-ribbon -->
  {/if}

  <div class="crm-block crm-content-block crm-contact-page crm-inline-edit-container">
    <div id="mainTabContainer">
      <ul class="crm-contact-tabs-list">
        {foreach from=$allTabs key=tabName item=tabValue}
          <li id="tab_{$tabValue.id}" class="crm-tab-button ui-corner-all crm-count-{$tabValue.count}{if isset($tabValue.class)} {$tabValue.class}{/if}">
            <a href="{$tabValue.url}" title="{$tabValue.title|escape}">
              {$tabValue.title}
              {if empty($tabValue.hideCount)}<em>{$tabValue.count}</em>{/if}
            </a>
          </li>
        {/foreach}
      </ul>

      <div id="contact-summary" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
        {if (isset($hookContentPlacement) and ($hookContentPlacement neq 3)) or empty($hookContentPlacement)}

          {if !empty($hookContent) and isset($hookContentPlacement) and $hookContentPlacement eq 2}
            {include file="CRM/Contact/Page/View/SummaryHook.tpl"}
          {/if}

          <div class="contactTopBar contact_panel">
            <div class="contactCardLeft">
              {crmRegion name="contact-basic-info-left"}
                <div class="crm-summary-contactinfo-block">
                  <div class="crm-summary-block" id="contactinfo-block">
                    {include file="CRM/Contact/Page/Inline/ContactInfo.tpl"}
                  </div>
                </div>
              {/crmRegion}
            </div> <!-- end of left side -->
            <div class="contactCardRight">
              {crmRegion name="contact-basic-info-right"}
              {if !empty($imageURL)}
                <div id="crm-contact-thumbnail">
                  {include file="CRM/Contact/Page/ContactImage.tpl"}
                </div>
              {/if}
                <div class="{if !empty($imageURL)} float-left{/if}">
                  <div class="crm-clear crm-summary-block">
                    {include file="CRM/Contact/Page/Inline/Basic.tpl"}
                  </div>
                </div>
              {/crmRegion}
            </div>
            <!-- end of right side -->
        </div>
        <div class="contact_details">
          <div class="contact_panel">
            <div class="contactCardLeft">
              {crmRegion name="contact-details-left"}
                <div >
                  {if $showEmail}
                    <div class="crm-summary-email-block crm-summary-block" id="email-block">
                      {include file="CRM/Contact/Page/Inline/Email.tpl"}
                    </div>
                  {/if}
                  {if $showWebsite}
                    <div class="crm-summary-website-block crm-summary-block" id="website-block">
                      {include file="CRM/Contact/Page/Inline/Website.tpl"}
                    </div>
                  {/if}
                </div>
              {/crmRegion}
            </div><!-- #contactCardLeft -->

            <div class="contactCardRight">
              {crmRegion name="contact-details-right"}
                <div>
                  {if $showPhone}
                    <div class="crm-summary-phone-block crm-summary-block" id="phone-block">
                      {include file="CRM/Contact/Page/Inline/Phone.tpl"}
                    </div>
                  {/if}
                  {if $showIM}
                    <div class="crm-summary-im-block crm-summary-block" id="im-block">
                      {include file="CRM/Contact/Page/Inline/IM.tpl"}
                    </div>
                  {/if}
                  {if $showOpenID}
                    <div class="crm-summary-openid-block crm-summary-block" id="openid-block">
                      {include file="CRM/Contact/Page/Inline/OpenID.tpl"}
                    </div>
                  {/if}
                </div>
              {/crmRegion}
            </div><!-- #contactCardRight -->

            <div class="clear"></div>
          </div><!-- #contact_panel -->
          {if $showAddress}
            <div class="contact_panel">
              {crmRegion name="contact-addresses"}
              {assign var='locationIndex' value=1}
              {if $address}
                {foreach from=$address item=add key=locationIndex}
                  <div class="{if $locationIndex is odd}contactCardLeft{else}contactCardRight{/if} crm-address_{$locationIndex} crm-address-block crm-summary-block">
                    {include file="CRM/Contact/Page/Inline/Address.tpl"}
                  </div>
                {/foreach}
                {assign var='locationIndex' value=$locationIndex+1}
              {/if}
              {* add new link *}
              {if $permission EQ 'edit'}
                {assign var='add' value=0}
                <div class="{if $locationIndex is odd}contactCardLeft{else}contactCardRight{/if} crm-address-block crm-summary-block">
                  {include file="CRM/Contact/Page/Inline/Address.tpl"}
                </div>
              {/if}
              {/crmRegion}
              </div> <!-- end of contact panel -->
            {/if}
            <div class="contact_panel">
              {if $showCommunicationPreferences}
                <div class="contactCardLeft">
                  {crmRegion name="contact-comm-pref"}
                  <div class="crm-summary-comm-pref-block">
                    <div class="crm-summary-block" id="communication-pref-block" >
                      {include file="CRM/Contact/Page/Inline/CommunicationPreferences.tpl"}
                    </div>
                  </div>
                  {/crmRegion}
                </div> <!-- contactCardLeft -->
              {/if}
              {if $contact_type eq 'Individual' AND $showDemographics}
                <div class="contactCardRight">
                  {crmRegion name="contact-demographic"}
                  <div class="crm-summary-demographic-block">
                    <div class="crm-summary-block" id="demographic-block">
                      {include file="CRM/Contact/Page/Inline/Demographics.tpl"}
                    </div>
                  </div>
                  {/crmRegion}
                </div> <!-- contactCardRight -->
              {/if}
              <div class="clear"></div>
                <div class="separator"></div>
              </div> <!-- contact panel -->
            </div><!--contact_details-->

            {if $showCustomData}
              <div id="customFields">
                <div class="contact_panel">
                  <div class="contactCardLeft">
                    {include file="CRM/Contact/Page/View/CustomDataView.tpl" side='1'}
                  </div><!--contactCardLeft-->
                  <div class="contactCardRight">
                    {include file="CRM/Contact/Page/View/CustomDataView.tpl" side='0'}
                  </div>

                  <div class="clear"></div>
                </div>
              </div>
            {/if}

            {if !empty($hookContent) and isset($hookContentPlacement) and $hookContentPlacement eq 1}
              {include file="CRM/Contact/Page/View/SummaryHook.tpl"}
            {/if}
          {else}
             {include file="CRM/Contact/Page/View/SummaryHook.tpl"}
          {/if}
        </div>
      <div class="clear"></div>
    </div>
    <div class="clear"></div>
  </div><!-- /.crm-content-block -->
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
