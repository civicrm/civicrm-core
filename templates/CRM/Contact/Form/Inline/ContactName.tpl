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
{* This file builds html for Contact Display Name inline edit *}
{$form.oplock_ts.html}
<div class="crm-inline-edit-form">
  <div class="crm-inline-button">
    {include file="CRM/common/formButtons.tpl"}
  </div>
  {if $contactType eq 'Individual'}
    {if $form.prefix_id}
      <div class="crm-inline-edit-field">
        {$form.prefix_id.label}<br/>
        {$form.prefix_id.html}
      </div>
    {/if}
    {if $form.formal_title}
      <div class="crm-inline-edit-field">
        {$form.formal_title.label}<br/>
        {$form.formal_title.html}
      </div>
    {/if}
    {if $form.first_name}
      <div class="crm-inline-edit-field">
        {$form.first_name.label}<br /> 
        {$form.first_name.html}
      </div>
    {/if}
    {if $form.middle_name}
      <div class="crm-inline-edit-field">
        {$form.middle_name.label}<br />
        {$form.middle_name.html}
      </div>
    {/if}
    {if $form.last_name}
      <div class="crm-inline-edit-field">
        {$form.last_name.label}<br />
        {$form.last_name.html}
      </div>
    {/if}
    {if $form.suffix_id}
      <div class="crm-inline-edit-field">
        {$form.suffix_id.label}<br/>
        {$form.suffix_id.html}
      </div>
    {/if}
  {elseif $contactType eq 'Organization'}
    <div class="crm-inline-edit-field">{$form.organization_name.label}&nbsp;
    {$form.organization_name.html}</div>
  {elseif $contactType eq 'Household'}
    <div class="crm-inline-edit-field">{$form.household_name.label}&nbsp;
    {$form.household_name.html}</div>
  {/if}
</div>
<div class="clear"></div>
