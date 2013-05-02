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
{if count( $wizard.steps ) > 1}
{* wizard.style variable is passed by some Wizards to allow alternate styling for progress "bar". *}
<div id="wizard-steps">
   <ul class="wizard-bar{if $wizard.style.barClass}-{$wizard.style.barClass}{/if}">
    {section name=step loop=$wizard.steps}
        {if count ( $wizard.steps ) > 5 }
            {* truncate step titles so header isn't too wide *}
            {assign var="title" value=$wizard.steps[step].title|crmFirstWord}
        {else}
            {assign var="title" value=$wizard.steps[step].title}
        {/if}
        {* Show each wizard link unless collapsed value is true. Also excluding quest app submit steps. Should create separate WizardHeader for Quest at some point.*}
        {if !$wizard.steps[step].collapsed && $wizard.steps[step].name NEQ 'Submit' && $wizard.steps[step].name NEQ 'PartnerSubmit'}
            {assign var=i value=$smarty.section.step.iteration}
            {if $wizard.currentStepNumber > $wizard.steps[step].stepNumber}
                {if $wizard.steps[step].step}
                    {assign var="stepClass" value="past-step"}
                {else} {* This is a sub-step *}
                    {assign var="stepClass" value="past-sub-step"}
                {/if}
                {if $wizard.style.hideStepNumbers}
                    {assign var="stepPrefix" value=$wizard.style.subStepPrefixPast}
                {else}
                    {assign var="stepPrefix" value=$wizard.style.stepPrefixPast|cat:$wizard.steps[step].stepNumber|cat:". "}
                {/if}
            {elseif $wizard.currentStepNumber == $wizard.steps[step].stepNumber}
                {if $wizard.steps[step].step}
                    {assign var="stepClass" value="current-step"}
                {else}
                    {assign var="stepClass" value="current-sub-step"}
                {/if}
                {if $wizard.style.hideStepNumbers}
                    {assign var="stepPrefix" value=$wizard.style.subStepPrefixCurrent}
                {else}
                    {assign var="stepPrefix" value=$wizard.style.stepPrefixCurrent|cat:$wizard.steps[step].stepNumber|cat:". "}
                {/if}
            {else}
                {if $wizard.steps[step].step}
                    {assign var="stepClass" value="future-step"}
                {else}
                    {assign var="stepClass" value="future-sub-step"}
                {/if}
                {if $wizard.style.hideStepNumbers}
                    {assign var="stepPrefix" value=$wizard.style.subStepPrefixFuture}
                {else}
                    {assign var="stepPrefix" value=$wizard.style.stepPrefixFuture|cat:$wizard.steps[step].stepNumber|cat:". "}
                {/if}
            {/if}
            {if !$wizard.steps[step].valid}
                {assign var="stepClass" value="$stepClass not-valid"}
            {/if}
            {* wizard.steps[step].link value is passed for wizards/steps which allow clickable navigation *} 
            <li class="{$stepClass}">{$stepPrefix}{if $wizard.steps[step].link}<a href="{$wizard.steps[step].link}">{/if}{$title}{if $wizard.steps[step].link}</a>{/if}</li>
        {/if} 
    {/section}
   </ul>
</div>
{if $wizard.style.showTitle}
    <h2>{$wizard.currentStepTitle} {ts 1=$wizard.currentStepNumber 2=$wizard.stepCount}(step %1 of %2){/ts}</h2>
{/if}
{/if}

