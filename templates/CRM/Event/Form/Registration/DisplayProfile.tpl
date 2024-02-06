{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{*display primary Participant Profile Information*}
    {if $primaryParticipantProfile}
        <div class="crm-group participant_info-group">
      <div class="header-dark">{if $addParticipantProfile}{ts}Participant 1{/ts}{else}{ts}Participant Information{/ts}{/if}</div>
            {if $primaryParticipantProfile.CustomPre}
               <fieldset class="label-left no-border"><div class="bold crm-profile-view-title">{$primaryParticipantProfile.CustomPreGroupTitle}</div>
                   {foreach from=$primaryParticipantProfile.CustomPre item=value key=field}
                      <div class="crm-public-form-item crm-section {$field}-section">
                          <div class="label">{$field}</div>
                          <div class="content">{$value}</div>
                          <div class="clear"></div>
                      </div>
                   {/foreach}
               </fieldset>
            {/if}
         {if array_key_exists('CustomPost', $primaryParticipantProfile) && $primaryParticipantProfile.CustomPost}
               {foreach from=$primaryParticipantProfile.CustomPost item=value key=field}
                  <fieldset class="label-left no-border"><div class="bold crm-profile-view-title">{$primaryParticipantProfile.CustomPostGroupTitle.$field.groupTitle}</div>
                    <div class="crm-profile-view">
                      {foreach from=$primaryParticipantProfile.CustomPost.$field item=value key=field}
                        <div class="crm-public-form-item crm-section {$field}-section">
                          <div class="label">{$field}</div>
                          <div class="content">{$value}</div>
                          <div class="clear"></div>
                        </div>
                      {/foreach}
                    </div>
                  </fieldset>
               {/foreach}
            {/if}
        </div>
        <div class="spacer"></div>
    {/if}

    {*display Additional Participant Profile Information*}
    {if $addParticipantProfile}
        {foreach from=$addParticipantProfile item=participant key=participantNo}
            <div class="crm-group participant_info-group">
                <div class="header-dark">
                    {ts 1=$participantNo}Participant %1{/ts}
                </div>
            {if $participant.additionalCustomPre}
              <fieldset class="label-left no-border"><div class="bold crm-additional-profile-view-title">{$participant.additionalCustomPreGroupTitle}</div>
                <div class="crm-profile-view">
                  {foreach from=$participant.additionalCustomPre item=value key=field}
                    <div class="crm-public-form-item crm-section {$field}-section">
                      <div class="label">{$field}</div>
                      <div class="content">{$value}</div>
                      <div class="clear"></div>
                    </div>
                  {/foreach}
                </div>
              </fieldset>
            {/if}

            {if $participant.additionalCustomPost}
              {foreach from=$participant.additionalCustomPost item=value key=field}
                <fieldset class="label-left no-border"><div class="bold crm-additional-profile-view-title">{$participant.additionalCustomPostGroupTitle.$field.groupTitle}</div>
                  <div class="crm-profile-view">
                    {foreach from=$participant.additionalCustomPost.$field item=value key=field}
                      <div class="crm-public-form-item crm-section {$field}-section">
                        <div class="label">{$field}</div>
                        <div class="content">{$value}</div>
                        <div class="clear"></div>
                      </div>
                    {/foreach}
                  </div>
                </fieldset>
              {/foreach}
            {/if}
            </div>
            <div class="spacer"></div>
        {/foreach}
    {/if}
