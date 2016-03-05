{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{* Search criteria form elements - Find Contacts *}

{* Set title for search criteria accordion *}
{capture assign=editTitle}{ts}Edit Search Criteria{/ts}{/capture}

{strip}
<div class="crm-block crm-form-block crm-basic-criteria-form-block">
    <div class="crm-accordion-wrapper crm-case_search-accordion {if $rows}collapsed{/if}">
     <div class="crm-accordion-header crm-master-accordion-header">
        {$editTitle}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
        <div class="crm-section sort_name-section">
          <div class="label">
            {$form.sort_name.label}
          </div>
          <div class="content">
            {$form.sort_name.html}
          </div>
          <div class="clear"></div>
        </div>

        {if $form.contact_type}
          <div class="crm-section contact_type-section">
            <div class="label">
              {$form.contact_type.label}
            </div>
              <div class="content">
                {$form.contact_type.html}
              </div>
              <div class="clear"></div>
          </div>
        {/if}

        {if $form.group}
        <div class="crm-section group_selection-section">
          <div class="label">
              {$form.group.label}
                </div>
          <div class="content">
                    {$form.group.html|crmAddClass:big}
                </div>
          <div class="clear"></div>
        </div>
        {/if}

        {if $form.tag}
            <div class="crm-section tag-section">
              <div class="label">
                {$form.tag.label}
              </div>
              <div class="content">
                {$form.tag.html|crmAddClass:medium}
              </div>
              <div class="clear"></div>
            </div>
        {/if}

          {* Choose regular or 'tall' listing-box class for Group select box based on # of groups. *}
            {if $form.custom_group|@count GT 8}
                {assign var="boxClass" value="listing-box-tall"}
            {else}
                {assign var="boxClass" value="listing-box"}
            {/if}
            <div class="crm-section crm-contact-custom-search-multipleValues-form-block-custom_group">
                <div class="label">
                  {ts}Custom Group(s){/ts}
                </div>
                <div class="content">
                    <div class="{$boxClass}">
                        {foreach from=$form.custom_group item="group_val"}
                            <div class="{cycle values="even-row,odd-row"}">
                                {$group_val.html}
                            </div>
                        {/foreach}
                    </div>
                </div>
            </div>

        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
    </div><!-- /.crm-accordion-body -->
    </div><!-- /.crm-accordion-wrapper -->
</div><!-- /.crm-form-block -->
{/strip}
