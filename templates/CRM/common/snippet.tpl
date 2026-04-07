{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}{strip}
  {if $config->debug}
    {include file="CRM/common/debug.tpl"}
  {/if}

  {if $smarty.get.snippet eq 4}
    {if $isForm}
      {include file="CRM/Form/default.tpl"}
    {else}
      {include file=$tplFile}
    {/if}
  {else}
    {if $smarty.get.snippet eq 1}
      {include file="CRM/common/print.tpl"}
    {else}
      {crmRegion name='ajax-snippet'}{/crmRegion}

      {crmRegion name='page-header' allowCmsOverride=0}{/crmRegion}

      {crmRegion name='page-body'}

        {* Add status messages and container-snippet div unless we are outputting json. *}
        {if $smarty.get.snippet neq 'json'}
          {* this div is deprecated but included for older-style snippets for legacy support *}
          <div class="crm-container-snippet">
          {include file="CRM/common/status.tpl"}
        {/if}

        {if $isForm}
          {include file="CRM/Form/default.tpl"}
        {else}
          {include file=$tplFile}
        {/if}

        {if $smarty.get.snippet neq 'json'}
          </div>
        {/if}

      {/crmRegion}

      {crmRegion name='page-footer' allowCmsOverride=0}{/crmRegion}
    {/if}
  {/if}
{/strip}
