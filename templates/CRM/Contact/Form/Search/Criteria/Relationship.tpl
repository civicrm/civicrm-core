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
<div id="relationship" class="form-item">
  <table class="form-layout">
    <tr>
      <td>
        {$form.relation_type_id.label}<br />
        {$form.relation_type_id.html}
      </td>
      <td>
         <div>
           {$form.relation_target_name.label}<br />
           {$form.relation_target_name.html|crmAddClass:huge}
            <div class="description font-italic">
                {ts}Complete OR partial contact name.{/ts}
            </div>
          </div>
      </td>
    </tr>
    <tr>
      <td>
         {$form.relation_status.label}<br />
         {$form.relation_status.html}
         </p>
         {$form.relation_permission.label}<br />
         {$form.relation_permission.html}
      </td>
      <td>
        {$form.relation_target_group.label} {help id="id-relationship-target-group" file="CRM/Contact/Form/Search/Advanced.hlp"}<br />
        {$form.relation_target_group.html|crmAddClass:huge}
      </td>
    </tr>
    <tr>
      <td colspan="2"><label>{ts}Start Date{/ts}</label></td>
    </tr>
    <tr>
      {include file="CRM/Core/DateRange.tpl" fieldName="relation_start_date" from='_low' to='_high'}
    </tr>
    <tr>
      <td colspan="2"><label>{ts}End Date{/ts}</label></td>
    </tr>
    <tr>
      {include file="CRM/Core/DateRange.tpl" fieldName="relation_end_date" from='_low' to='_high'}
    </tr>
    {if $relationshipGroupTree}
      <tr>
      <td colspan="2">
        {include file="CRM/Custom/Form/Search.tpl" groupTree=$relationshipGroupTree showHideLinks=false}
      </td>
      </tr>
    {/if}
  </table>
</div>
