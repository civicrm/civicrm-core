-- CRM-5030

    UPDATE civicrm_navigation
        SET url = 'civicrm/activity/add?atype=3&action=add&reset=1&context=standalone'
        WHERE civicrm_navigation.name= 'New Email';
    UPDATE civicrm_navigation
        SET url = 'civicrm/contribute/add&reset=1&action=add&context=standalone'
        WHERE civicrm_navigation.name= 'New Contribution';
    UPDATE civicrm_navigation
        SET url = 'civicrm/pledge/add&reset=1&action=add&context=standalone'
        WHERE civicrm_navigation.name= 'New Pledge';
    UPDATE civicrm_navigation
        SET url = 'civicrm/participant/add&reset=1&action=add&context=standalone'
        WHERE civicrm_navigation.name= 'Register Event Participant';
    UPDATE civicrm_navigation
        SET url = 'civicrm/member/add&reset=1&action=add&context=standalone'
        WHERE civicrm_navigation.name= 'New Membership';
    UPDATE civicrm_navigation
        SET url = 'civicrm/case/add&reset=1&action=add&atype=13&context=standalone'
        WHERE civicrm_navigation.name= 'New Case';
    UPDATE civicrm_navigation
        SET url = 'civicrm/grant/add&reset=1&action=add&context=standalone'
        WHERE civicrm_navigation.name= 'New Grant';

-- CRM-5048
INSERT INTO civicrm_state_province (id,    country_id, abbreviation, name) VALUES
                                   (10010, 1107,       "Bar",        "Barletta-Andria-Trani"),
                                   (10011, 1107,       "Fer",        "Fermo"),
                                   (10012, 1107,       "Mon",        "Monza e Brianza");

-- CRM-5031
{if $multilingual}
  {foreach from=$locales item=locale}
    ALTER TABLE civicrm_mailing ADD name_{$locale}      VARCHAR(128);
    ALTER TABLE civicrm_mailing ADD from_name_{$locale} VARCHAR(128);
    ALTER TABLE civicrm_mailing ADD subject_{$locale}   VARCHAR(128);
    ALTER TABLE civicrm_mailing ADD body_text_{$locale} TEXT;
    ALTER TABLE civicrm_mailing ADD body_html_{$locale} TEXT;

    UPDATE civicrm_mailing SET name_{$locale}      = name;
    UPDATE civicrm_mailing SET from_name_{$locale} = from_name;
    UPDATE civicrm_mailing SET subject_{$locale}   = subject;
    UPDATE civicrm_mailing SET body_text_{$locale} = body_text;
    UPDATE civicrm_mailing SET body_html_{$locale} = body_html;
  {/foreach}

  ALTER TABLE civicrm_mailing DROP name;
  ALTER TABLE civicrm_mailing DROP from_name;
  ALTER TABLE civicrm_mailing DROP subject;
  ALTER TABLE civicrm_mailing DROP body_text;
  ALTER TABLE civicrm_mailing DROP body_html;

  {foreach from=$locales item=locale}
    ALTER TABLE civicrm_mailing_component ADD name_{$locale}      VARCHAR(64);
    ALTER TABLE civicrm_mailing_component ADD subject_{$locale}   VARCHAR(255);
    ALTER TABLE civicrm_mailing_component ADD body_text_{$locale} TEXT;
    ALTER TABLE civicrm_mailing_component ADD body_html_{$locale} TEXT;

    UPDATE civicrm_mailing_component SET name_{$locale}      = name;
    UPDATE civicrm_mailing_component SET subject_{$locale}   = subject;
    UPDATE civicrm_mailing_component SET body_text_{$locale} = body_text;
    UPDATE civicrm_mailing_component SET body_html_{$locale} = body_html;
  {/foreach}

  ALTER TABLE civicrm_mailing_component DROP name;
  ALTER TABLE civicrm_mailing_component DROP subject;
  ALTER TABLE civicrm_mailing_component DROP body_text;
  ALTER TABLE civicrm_mailing_component DROP body_html;
{/if}
