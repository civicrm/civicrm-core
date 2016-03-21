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

<div class="crm-block crm-form-block crm-mailing-group-form-block">
{include file="CRM/common/WizardHeader.tpl"}

  <table class="form-layout">
   <tr class="crm-mailing-group-form-block-name"><td class="label">{$form.name.label}</td><td>{$form.name.html} {help id="mailing-name"}</td></tr>
     {* CRM-7362 --add campaign *}
     {include file="CRM/Campaign/Form/addCampaignToComponent.tpl"
     campaignTrClass="crm-mailing-group-form-block-campaign_id"}

    {if $context EQ 'search'}
        <tr class="crm-mailing-group-form-block-baseGroup">
            <td class="label">{$form.baseGroup.label}</td>
            <td>{$form.baseGroup.html} {help id="base-group"}</td>
        </tr>
    {/if}

    <tr class="crm-mailing-group-form-block-dedupeemail">
        <td class="label">{$form.dedupe_email.label}</td>
        <td>{$form.dedupe_email.html} {help id="dedupe-email"}</td>
    </tr>
    <tr class="crm-mailing-group-form-block-locationTypeId">
        <td class="label">{$form.location_type_id.label}</td>
        <td>{$form.location_type_id.html}</td>
    </tr>
    <tr class="crm-mailing-group-form-block-locationSelectionMethod">
        <td class="label">{$form.email_selection_method.label}</td>
        <td>{$form.email_selection_method.html} {help id="email-selection"}</td>
    </tr>

  </table>

{if ($groupCount > 0|| $mailingCount > 0)}
<div id="id-additional" class="form-item">
<div class="crm-accordion-wrapper ">
 <div class="crm-accordion-header">
 {if $context EQ 'search'}{ts}Additional Mailing Recipients{/ts}{else}{ts}Mailing Recipients{/ts}{/if}
 </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">
  {strip}

  <table>
  {if $groupCount > 0}
    <tr class="crm-mailing-group-form-block-includeGroups"><td class="label">{$form.includeGroups.label} {help id="include-groups"}</td></tr>
    <tr class="crm-mailing-group-form-block-includeGroups"><td>{$form.includeGroups.html}</td></tr>
    <tr class="crm-mailing-group-form-block-excludeGroups"><td class="label">{$form.excludeGroups.label} {help id="exclude-groups"}</td></tr>
    <tr class="crm-mailing-group-form-block-excludeGroups"><td>{$form.excludeGroups.html}</td></tr>
  {/if}
  {if $mailingCount > 0}
  <tr class="crm-mailing-group-form-block-includeMailings"><td class="label">{$form.includeMailings.label} {help id="include-mailings"}</td></tr>
  <tr class="crm-mailing-group-form-block-includeMailings"><td>{$form.includeMailings.html}</td></tr>
  <tr class="crm-mailing-group-form-block-excludeMailings"><td class="label">{$form.excludeMailings.label} {help id="exclude-mailings"}</td></tr>
  <tr class="crm-mailing-group-form-block-excludeMailings"><td>{$form.excludeMailings.html}</td></tr>
  {/if}
  </table>

  {/strip}
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
{/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</div>
</div>

