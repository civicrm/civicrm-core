{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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

{if $profileFields}
    <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
    <div class="crm-accordion-header">
        <div class="icon crm-accordion-pointer"></div>
        {ts}{$profileTitle}{/ts}                    
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
 	    {* here build the profile fields *}
        <table class="form-layout-compressed">
        {foreach from=$profileFields item=field key=fieldName}
            <tr class="{$field.data_type} {$fieldName}">
                <td class='label'>{$form.$fieldName.label}</td>
                <td>
                {if ( $field.data_type eq 'Date') or 
                    ( $fieldName eq 'birth_date' ) or (  $fieldName eq 'deceased_date' ) }
                    {if $action eq 1 or $action eq 2}
                        {include file="CRM/common/jcalendar.tpl" elementName=$fieldName}
                    {else}
                        {$form.$fieldName.html}
                    {/if}
                {else}
                   {$form.$fieldName.html}
                {/if}
		        {if $field.html_type eq 'Autocomplete-Select'}
		            {include file="CRM/Custom/Form/AutoComplete.tpl" element_name = field[`$fieldName`]}
                {/if}
		        </td>
            </tr> 
        {/foreach}
        </table>
    </div><!-- /.crm-accordion-body -->
    </div><!-- /.crm-accordion-wrapper -->

    {literal} 
    <script type="text/javascript">
    cj(function() {
        cj().crmaccordions(); 
     });
    </script>
    {/literal}
{/if}

