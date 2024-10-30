{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if count( $wizard.steps ) > 1}
{* wizard.style variable is passed by some Wizards to allow alternate styling for progress "bar". *}
<div id="wizard-steps">
   <ul class="wizard-bar{if $wizard.style.barClass}-{$wizard.style.barClass}{/if}">
    {section name=step loop=$wizard.steps}
        {if count ( $wizard.steps ) > 5}
            {* truncate step titles so header isn't too wide *}
            {assign var="title" value=$wizard.steps[step].title|crmFirstWord}
        {else}
            {assign var="title" value=$wizard.steps[step].title}
        {/if}
        {* Show each wizard link unless collapsed value is true. Also excluding quest app submit steps. Should create separate WizardHeader for Quest at some point.*}
        {if !$wizard.steps[step].collapsed && $wizard.steps[step].name && $wizard.steps[step].name NEQ 'Submit' && $wizard.steps[step].name NEQ 'PartnerSubmit'}
            {assign var=i value=$smarty.section.step.iteration}
            {if $wizard.currentStepNumber > $wizard.steps[step].stepNumber}
                {if $wizard.steps[step].step}
                    {assign var="stepClass" value="past-step"}
                {else} {* This is a sub-step *}
                    {assign var="stepClass" value="past-sub-step"}
                {/if}
                {assign var="stepPrefix" value=$wizard.style.stepPrefixPast|cat:$wizard.steps[step].stepNumber|cat:". "}
            {elseif $wizard.currentStepNumber == $wizard.steps[step].stepNumber}
                {if $wizard.steps[step].step}
                    {assign var="stepClass" value="current-step"}
                {else}
                    {assign var="stepClass" value="current-sub-step"}
                {/if}
                {assign var="stepPrefix" value=$wizard.style.stepPrefixCurrent|smarty:nodefaults|cat:$wizard.steps[step].stepNumber|cat:". "}
            {else}
                {if $wizard.steps[step].step}
                    {assign var="stepClass" value="future-step"}
                {else}
                    {assign var="stepClass" value="future-sub-step"}
                {/if}
                {assign var="stepPrefix" value=$wizard.style.stepPrefixFuture|smarty:nodefaults|cat:$wizard.steps[step].stepNumber|cat:". "}
            {/if}
            {if !$wizard.steps[step].valid}
                {assign var="stepClass" value="$stepClass not-valid"}
            {/if}
            {* wizard.steps[step].link value is passed for wizards/steps which allow clickable navigation *}
            <li class="{$stepClass}">{$stepPrefix|smarty:nodefaults}{if $wizard.steps[step].link}<a href="{$wizard.steps[step].link}">{/if}{$title}{if $wizard.steps[step].link}</a>{/if}</li>
        {/if}
    {/section}
   </ul>
</div>
{if !empty($wizard.style.showTitle)}
    <h2>{$wizard.currentStepTitle} {ts 1=$wizard.currentStepNumber 2=$wizard.stepCount}(step %1 of %2){/ts}</h2>
{/if}
{/if}

