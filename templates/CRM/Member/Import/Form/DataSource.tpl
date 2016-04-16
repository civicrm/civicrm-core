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
 <div class="crm-block crm-form-block crm-member-import-uploadfile-form-block">
{* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
 {include file="CRM/common/WizardHeader.tpl"}
<div class="help">
    {ts}The Membership Import Wizard allows you to easily upload memberships from other applications into CiviCRM.{/ts}
    {ts}Files to be imported must be in the 'comma-separated-values' format (CSV) and must contain data needed to match the membership data to an existing contact in your CiviCRM database.{/ts} {help id='upload'}
 </div>
{* Membership Import Wizard - Step 1 (upload data file) *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
   <table class="form-layout">
      <div id="upload-file" class="form-item">
       <tr class="crm-member-import-uploadfile-from-block-uploadFile">
           <td class="label">{$form.uploadFile.label}</td>
           <td>{$form.uploadFile.html}<br />
                <span class="description">{ts}File format must be comma-separated-values (CSV).{/ts}</span>
                 <br /><span>{ts 1=$uploadSize}Maximum Upload File Size: %1 MB{/ts}</span>
           </td>
       </tr>
       <tr class="crm-member-import-uploadfile-from-block-skipColumnHeader">
           <td class="label">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$form.skipColumnHeader.html} </td>
     <td>{$form.skipColumnHeader.label}<br />
               <span class="description">
                {ts}Check this box if the first row of your file consists of field names (Example: 'Contact ID', 'Amount').{/ts}</span>
           </td>
       <tr class="crm-member-import-uploadfile-from-block-contactType">
           <td class="label">{$form.contactType.label}</tdt>
     <td>{$form.contactType.html}<br />
                <span class="description">
                {ts}Select 'Individual' if you are importing memberships for individual persons.{/ts}
                {ts}Select 'Organization' or 'Household' if you are importing memberships made by contacts of that type. (NOTE: Some built-in contact types may not be enabled for your site.){/ts}
                </span>
           </td>
       </tr>
       <tr class="crm-member-import-uploadfile-from-block-onDuplicate">
           <td class="label" >{$form.onDuplicate.label}</td>
           <td>{$form.onDuplicate.html} {help id="id-onDuplicate"}</td>
       </tr>
        <tr class="crm-import-datasource-form-block-fieldSeparator">
          <td class="label">{$form.fieldSeparator.label} {help id='id-fieldSeparator' file='CRM/Contact/Import/Form/DataSource'}</td>
          <td>{$form.fieldSeparator.html}</td>
        </tr>
       <tr class="crm-member-import-uploadfile-from-block-date">{include file="CRM/Core/Date.tpl"}</tr>
{if $savedMapping}
       <tr  class="crm-member-import-uploadfile-from-block-savedMapping">
         <td>{if $loadedMapping}{ts}Select a Different Field Mapping{/ts}{else}{ts}Load Saved Field Mapping{/ts}{/if}</td>
         <td>{$form.savedMapping.html}<br />
           <span class="description">{ts}If you want to use a previously saved import field mapping - select it here.{/ts}</span>
         </td>
       </tr>
{/if}
</div>
</table>
<div class="spacer"></div>

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
