{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

{* Loops through $form.buttons.html array and assigns separate spans with classes to allow theming
   by button and name. crmBtnType grabs type keyword from button name (e.g. 'upload', 'next', 'back', 'cancel') so
   types of buttons can be styled differently via css. *}
   
{foreach from=$form.buttons item=button key=key name=btns}
    {if $key|substring:0:4 EQ '_qf_'}
        {if $location}
          {assign var='html' value=$form.buttons.$key.html|crmReplace:id:"$key-$location"}
        {else}
          {assign var='html' value=$form.buttons.$key.html}
        {/if}
        {capture assign=validate}{$key|crmBtnValidate}{/capture}
        <span class="crm-button crm-button-type-{$key|crmBtnType} crm-button{$key}"{if $buttonStyle} style="{$buttonStyle}"{/if}>{$html|crmAddClass:$validate}</span>
    {/if}
{/foreach}
