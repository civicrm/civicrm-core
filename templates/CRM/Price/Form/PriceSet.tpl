{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{crmRegion name="price-set-1"}
<div id="priceset" class="crm-section price_set-section">
    {if $priceSet.help_pre}
        <div class="messages help">{$priceSet.help_pre}</div>
    {/if}

    {assign var='adminFld' value=false}
    {if call_user_func(array('CRM_Core_Permission','check'), 'administer CiviCRM') }
      {assign var='adminFld' value=true}
      {if $priceSet.id && !$priceSet.is_quick_config}
        <div class='float-right'>
          <a class="crm-hover-button" target="_blank" href="{crmURL p="civicrm/admin/price/field" q="reset=1&action=browse&sid=`$priceSet.id`" fb=1}">
            {icon icon="fa-wrench"}{ts}Edit Price Set{/ts}{/icon}
          </a>
        </div>
      {/if}
    {/if}

    {foreach from=$priceSet.fields item=element key=field_id}
        {* Skip 'Admin' visibility price fields WHEN this tpl is used in online registration unless user has administer CiviCRM permission. *}
        {if $element.visibility EQ 'public' || ($element.visibility EQ 'admin' && $adminFld EQ true) || $context eq 'standalone' || $context eq 'advanced' || $context eq 'search' || $context eq 'participant' || $context eq 'dashboard' || $action eq 1024}
            {if $element.help_pre}<span class="content description">{$element.help_pre}</span><br />{/if}
            <div class="crm-section {$element.name}-section">
            {if ($element.html_type eq 'CheckBox' || $element.html_type == 'Radio') && $element.options_per_line}
              {assign var="element_name" value="price_"|cat:$field_id}
              <div class="label">{$form.$element_name.label}</div>
              <div class="content {$element.name}-content">
                {assign var="elementCount" value="0"}
                {assign var="optionCount" value="0"}
                {assign var="rowCount" value="0"}
                {foreach name=outer key=key item=item from=$form.$element_name}
                  {assign var="elementCount" value=`$elementCount+1`}
                  {if is_numeric($key) }
                    {assign var="optionCount" value=`$optionCount+1`}
                    {if $optionCount == 1}
                      {assign var="rowCount" value=`$rowCount+1`}
                      <div class="price-set-row {$element.name}-row{$rowCount}">
                    {/if}
                    <span class="price-set-option-content">{$form.$element_name.$key.html}</span>
                    {if $optionCount == $element.options_per_line || $elementCount == $form.$element_name|@count}
                      </div>
                      {assign var="optionCount" value="0"}
                    {/if}
                  {/if}
                {/foreach}
                {if $element.help_post}
                  <div class="description">{$element.help_post}</div>
                {/if}
              </div>
            {else}

                {assign var="element_name" value="price_"|cat:$field_id}

                <div class="label">{$form.$element_name.label}</div>
                <div class="content {$element.name}-content">
                  {$form.$element_name.html}
                  {if $element.html_type eq 'Text'}
                    {if $element.is_display_amounts}
                    <span class="price-field-amount{if $form.$element_name.frozen EQ 1} sold-out-option{/if}">
                    {foreach item=option from=$element.options}
                      {if ($option.tax_amount || $option.tax_amount == "0") && $displayOpt && $invoicing}
                        {assign var="amount" value=`$option.amount+$option.tax_amount`}
                        {if $displayOpt == 'Do_not_show'}
                          {$amount|crmMoney}
                        {elseif $displayOpt == 'Inclusive'}
                          {$amount|crmMoney}
                          <span class='crm-price-amount-label'> {ts 1=$taxTerm 2=$option.tax_amount|crmMoney}(includes %1 of %2){/ts}</span>
                        {else}
                          {$option.amount|crmMoney}
                          <span class='crm-price-amount-label'> + {$option.tax_amount|crmMoney} {$taxTerm}</span>
                        {/if}
                      {else}
                        {$option.amount|crmMoney} {$fieldHandle} {$form.$fieldHandle.frozen}
                      {/if}
                      {if $form.$element_name.frozen EQ 1} ({ts}Sold out{/ts}){/if}
                    {/foreach}
                    </span>
                    {else}
                      {* Not showing amount, but still need to conditionally show Sold out marker *}
                      {if $form.$element_name.frozen EQ 1}
                        <span class="sold-out-option">({ts}Sold out{/ts})<span>
                      {/if}
                    {/if}
                  {/if}
                  {if $element.help_post}<br /><span class="description">{$element.help_post}</span>{/if}
                </div>

            {/if}
              {if !empty($extends) && $extends eq "Membership"}
                {if (!empty($priceSet) && $element.id == $priceSet.auto_renew_membership_field) || (empty($priceSet) && $element.name == 'membership_amount')}
                  <div id="allow_auto_renew">
                    <div class='crm-section auto-renew'>
                      <div class='label'></div>
                      <div class='content' id="auto_renew_section">
                        {if isset($form.auto_renew) }
                          {$form.auto_renew.html}&nbsp;{$form.auto_renew.label}
                        {/if}
                      </div>
                      <div class='content' id="force_renew" style='display: none'>{ts}Membership will renew automatically.{/ts}</div>
                    </div>
                  </div>
                {/if}
              {/if}
              <div class="clear"></div>
          </div>
        {/if}
    {/foreach}

    {if $priceSet.help_post}
      <div class="messages help">{$priceSet.help_post}</div>
    {/if}

    {include file="CRM/Price/Form/Calculate.tpl"}
</div>
{/crmRegion}
