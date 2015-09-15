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
         {if $primaryParticipantProfile.CustomPost}
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
