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
        <div class="messages help">{$priceSet.help_pre|purify}</div>
    {/if}

    {crmPermission has='administer CiviCRM'}
      {if $priceSet.id && !$priceSet.is_quick_config}
        <div class='float-right'>
          <a class="crm-hover-button" target="_blank" href="{crmURL p="civicrm/admin/price/field" q="reset=1&action=browse&sid=`$priceSet.id`" fb=1}">
            {icon icon="fa-wrench"}{ts}Edit Price Set{/ts}{/icon}
          </a>
        </div>
      {/if}
    {/crmPermission}

    {foreach from=$priceSet.fields item=element key=field_id}
        {* Skip 'Admin' visibility price fields WHEN this tpl is used in online registration unless user has administer CiviCRM permission. *}
        {if $element.visibility !== 'admin' || isShowAdminVisibilityFields}
            {if $element.help_pre}<span class="content description">{$element.help_pre|purify}</span><br />{/if}
            <div class="crm-section {$element.name|escape}-section crm-price-field-id-{$field_id}">
            {if ($element.html_type eq 'CheckBox' || $element.html_type == 'Radio') && $element.options_per_line}
              {assign var="element_name" value="price_`$field_id`"}
              <div class="label">{$form.$element_name.label}</div>
              <div class="content {$element.name|escape}-content">
                {assign var="elementCount" value="0"}
                {assign var="optionCount" value="0"}
                {assign var="rowCount" value="0"}
                {foreach name=outer key=key item=item from=$form.$element_name}
                  {assign var="elementCount" value=$elementCount+1}
                  {if is_numeric($key)}
                    {assign var="optionCount" value=$optionCount+1}
                    {if $optionCount == 1}
                      {assign var="rowCount" value=$rowCount+1}
                      <div class="price-set-row {$element.name|escape}-row{$rowCount}">
                    {/if}
                    <span class="price-set-option-content">{$form.$element_name.$key.html}</span>
                    {if $optionCount == $element.options_per_line || $elementCount == $form.$element_name|@count}
                      </div>
                      {assign var="optionCount" value="0"}
                    {/if}
                  {/if}
                {/foreach}
                {if $element.help_post}
                  <div class="description">{$element.help_post|purify}</div>
                {/if}
              </div>
            {else}

                {assign var="element_name" value="price_"|cat:$field_id}

                <div class="label">{$form.$element_name.label}</div>
                <div class="content {$element.name|escape}-content">
                  {$form.$element_name.html}
                  {if $element.html_type eq 'Text'}
                    {if $element.is_display_amounts}
                    <span class="price-field-amount{if $form.$element_name.frozen EQ 1} sold-out-option{/if}">
                    {foreach item=option from=$element.options}
                      {if ($option.tax_amount || $option.tax_amount == "0") && $displayOpt && $invoicing}
                        {assign var="amount" value=$option.amount+$option.tax_amount}
                        {if $displayOpt == 'Do_not_show'}
                          {$amount|crmMoney:$currency}
                        {elseif $displayOpt == 'Inclusive'}
                          {$amount|crmMoney:$currency}
                          <span class='crm-price-amount-tax'> {ts 1=$taxTerm 2=$option.tax_amount|crmMoney:$currency}(includes %1 of %2){/ts}</span>
                        {else}
                          {$option.amount|crmMoney:$currency}
                          <span class='crm-price-amount-tax'> + {$option.tax_amount|crmMoney:$currency} {$taxTerm}</span>
                        {/if}
                      {else}
                        {$option.amount|crmMoney:$currency} {$fieldHandle} {$form.$fieldHandle.frozen}
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
                  {if $element.help_post}<br /><span class="description">{$element.help_post|purify}</span>{/if}
                </div>

            {/if}
            {if (array_key_exists('auto_renew', $form)) && !empty($extends) && $extends eq "Membership" && array_key_exists('supports_auto_renew', $element) && $element.supports_auto_renew}
              <div id="allow_auto_renew">
                <div class='crm-section auto-renew'>
                  <div class='label'></div>
                  <div class='content' id="auto_renew_section">
                    {if $form.auto_renew}
                      {$form.auto_renew.html}&nbsp;{$form.auto_renew.label}
                    {/if}
                  </div>
                  <div class='content' id="force_renew" style='display: none'>{ts}Membership will renew automatically.{/ts}</div>
                </div>
              </div>
            {/if}
              <div class="clear"></div>
          </div>
        {/if}
    {/foreach}

    {if $priceSet.help_post}
      <div class="messages help">{$priceSet.help_post|purify}</div>
    {/if}

    {include file="CRM/Price/Form/Calculate.tpl"}
</div>
{/crmRegion}
