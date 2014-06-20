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
<div id="priceset" class="crm-section price_set-section">
    {if $priceSet.help_pre}
        <div class="messages help">{$priceSet.help_pre}</div>
    {/if}

		{assign var='adminFld' value=false}
		{if call_user_func(array('CRM_Core_Permission','check'), 'administer CiviCRM') }
			{assign var='adminFld' value=true}
		{/if}

    {foreach from=$priceSet.fields item=element key=field_id}
        {* Skip 'Admin' visibility price fields WHEN this tpl is used in online registration unless user has administer CiviCRM permission. *}
        {if $element.visibility EQ 'public' || ($element.visibility EQ 'admin' && $adminFld EQ true) || $context eq 'standalone' || $context eq 'advanced' || $context eq 'search' || $context eq 'participant' || $context eq 'dashboard' || $action eq 1024}
            <div class="crm-section {$element.name}-section">
            {if ($element.html_type eq 'CheckBox' || $element.html_type == 'Radio') && $element.options_per_line}
              {assign var="element_name" value="price_"|cat:$field_id}
          <div class="label">{$form.$element_name.label}</div>
                <div class="content {$element.name}-content">
                {assign var="rowCount" value="1"}
                {assign var="count" value="1"}
                {foreach name=outer key=key item=item from=$form.$element_name}
                    {if is_numeric($key) }
                        {if $count == 1}<div class="price-set-row {$element.name}-row{$rowCount}">{/if}
                        <span class="price-set-option-content">{$form.$element_name.$key.html}</span>
                        {if $count == $element.options_per_line}
                          </div>
                          {assign var="rowCount" value=`$rowCount+1`}
                          {assign var="count" value="1"}
                        {else}
                          {assign var="count" value=`$count+1`}
                        {/if}
                    {/if}
                {/foreach}
                {if $element.help_post}
                    <div class="description">{$element.help_post}</div>
                {/if}
                </div>
                <div class="clear"></div>

            {else}

                {assign var="element_name" value="price_"|cat:$field_id}

                <div class="label">{$form.$element_name.label}</div>
                <div class="content {$element.name}-content">{$form.$element_name.html}
                  {if $element.is_display_amounts && $element.html_type eq 'Text'}
                    <span class="price-field-amount">
                      {foreach item=option from=$element.options}{$option.amount|crmMoney}{/foreach}
                    </span>
                  {/if}
                      {if $element.help_post}<br /><span class="description">{$element.help_post}</span>{/if}
                </div>
                <div class="clear"></div>

            {/if}
            </div>
        {/if}
    {/foreach}

    {if $priceSet.help_post}
      <div class="messages help">{$priceSet.help_post}</div>
    {/if}

{* Include the total calculation widget if this is NOT a quickconfig event/contribution page. *}
{if !$quickConfig and !$dontInclCal}
    {include file="CRM/Price/Form/Calculate.tpl"}
{/if}
</div>