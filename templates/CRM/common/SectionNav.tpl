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
{* Navigation template for multi-section Wizards *}
{if count( $category.steps ) > 0}
<div id="wizard-steps">
   <ul class="section-list">
    {foreach from=$category.steps item=step}
            {assign var=i value=$smarty.section.step.iteration}
            {if $step.current}
                {assign var="stepClass" value="current-section"}
            {else}
                {assign var="stepClass" value="future-section"}
            {/if}
            {if !$step.valid}
                {assign var="stepClass" value="$stepClass not-valid"}
            {/if}
            {* Skip "Submit Application" category - it is shown separately *}
            {if $step.title NEQ 'Submit Application'}
                {* step.link value is passed for section usages which allow clickable navigation AND when section state is clickable *}
                <li class="{$stepClass}">{if $step.link && !$step.current}<a href="{$step.link}">{/if}{$step.title}{if $step.link && !$step.current}</a>{/if}</li>
                {if $step.current}
                    {include file="CRM/common/WizardHeader.tpl"}
                {/if}
            {/if}
    {/foreach}
   </ul>
</div>
{/if}
