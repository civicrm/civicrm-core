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
{* Update Grants *}
<div class="crm-block crm-form-block crm-grants-update-form-block">
    <p>{ts}Enter values for the fields you wish to update. Leave fields blank to preserve existing values.{/ts}</p>
    <table class="form-layout-compressed">
        {* Loop through all defined search criteria fields (defined in the buildForm() function). *}
        {foreach from=$elements item=element}
            <tr class="crm-contact-custom-search-form-row-{$element}">
                <td class="label">{$form.$element.label}</td>
                {if $element eq 'decision_date'}
                    <td>{include file="CRM/common/jcalendar.tpl" elementName=decision_date}<br />
                    <span class="description">{ts}Date on which the grant decision was finalized.{/ts}</span></td>
                {else}
                    <td>{$form.$element.html}</td>
                {/if}
            </tr>
        {/foreach}
    </table>
    <p>{ts 1=$totalSelectedGrants}Number of selected grants: %1{/ts}</p>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div><!-- /.crm-form-block -->
