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
    {if $smarty.get.snippet eq 2}
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

        <!-- .tpl file invoked: {$tplFile}. Call via form.tpl if we have a form in the page. -->
        {if !empty($isForm)}
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
