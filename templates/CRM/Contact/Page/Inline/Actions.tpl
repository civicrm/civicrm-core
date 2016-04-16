{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{* this template is used for the dropdown menu of the "Actions" button on contacts. *}

<div id="crm-contact-actions-wrapper" data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Page_Inline_Actions"{rdelim}'>
  {crmButton id="crm-contact-actions-link" href="#" icon="bars"}
    {ts}Actions{/ts}
  {/crmButton}
    <div class="ac_results" id="crm-contact-actions-list">
      <div class="crm-contact-actions-list-inner">
        <div class="crm-contact_activities-list">
        {include file="CRM/Activity/Form/ActivityLinks.tpl" as_select=false}
        </div>

              <div class="crm-contact_print-list">
              <ul class="contact-print">
                  <li class="crm-contact-print">
                     <a class="print" title="{ts}Printer-friendly view of this page.{/ts}" href='{crmURL p='civicrm/contact/view/print' q="reset=1&print=1&cid=$contactId"}'>
                     <span><i class="crm-i fa-print"></i> {ts}Print Summary{/ts}</span>
                     </a>
                  </li>
                  <li>
                        <a class="vcard " title="{ts}vCard record for this contact.{/ts}" href="{crmURL p='civicrm/contact/view/vcard' q="reset=1&cid=$contactId"}"><span><i class="crm-i fa-list-alt"></i> {ts}vCard{/ts}</span>
                        </a>
                  </li>
                 {if !empty($dashboardURL)}
                   <li class="crm-contact-dashboard">
                      <a href="{$dashboardURL}" class="dashboard " title="{ts}dashboard{/ts}">
                         <span><i class="crm-i fa-tachometer"></i> {ts}Contact Dashboard{/ts}</span>
                       </a>
                   </li>
                 {/if}
                 {if !empty($userRecordUrl)}
                   <li class="crm-contact-user-record">
                      <a href="{$userRecordUrl}" class="user-record " title="{ts}User Record{/ts}">
                         <span><i class="crm-i fa-user"></i> {ts}User Record{/ts}</span>
                      </a>
                   </li>
                 {/if}
                 {if !empty($userAddUrl)}
                   <li class="crm-contact-user-record">
                      <a href="{$userAddUrl}" class="user-record " title="{ts}Create User Record{/ts}">
                         <span><i class="crm-i fa-user-plus"></i> {ts}Create User Record{/ts}</span>
                      </a>
                   </li>
              {/if}
        </ul>
        </div>
        <div class="crm-contact_actions-list">
        <ul class="contact-actions">
          {foreach from=$actionsMenuList.moreActions item='row'}
          {if !empty($row.href) or !empty($row.tab)}
          <li class="crm-action-{$row.ref}">
            <a href="{if !empty($row.href)}{$row.href}&cid={$contactId}{else}#{/if}" title="{$row.title}" data-tab="{$row.tab}" {if !empty($row.class)}class="{$row.class}"{/if}>{$row.title}</a>
          </li>
          {/if}
        {/foreach}
              </ul>
              </div>


        <div class="clear"></div>
      </div>
    </div>
  </div>
{literal}
{/literal}
