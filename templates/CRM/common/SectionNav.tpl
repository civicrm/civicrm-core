{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Navigation template for multi-section Wizards *}
{if $category.steps}
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
