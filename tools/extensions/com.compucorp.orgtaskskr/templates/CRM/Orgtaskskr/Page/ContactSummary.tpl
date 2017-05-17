<a href="{crmURL p='civicrm/profile/view' q="reset=1&gid=7&id=`$contact.contact_id`&snippet=4"}" class="crm-summary-link">
  <div class="icon crm-icon {$contact.inner_contact_type}-icon"></div>
  <div class="crm-tooltip-wrapper">
    <div class="crm-tooltip">            
      <div class="crm-summary-group">
        <table class="crm-table-group-summary">
          <tbody><tr><td>{$contact.display_name}</td></tr>
            <tr><td>
                <div class="crm-summary-col-0">
                  <div class="crm-section phone_1_1-section">
                    <div class="label">
                      Home Phone
                    </div>
                    <div class="content">
                      {$contact.inner_phone}
                    </div>
                    <div class="clear"></div>
                  </div>
                  <div class="crm-section phone_1_2-section">
                    <div class="label">
                      Home Mobile
                    </div>
                    <div class="content">
                      {$contact.inner_phone}
                    </div>
                    <div class="clear"></div>
                  </div>
                  <div class="crm-section street_address_Primary-section">
                    <div class="label">
                      Primary Address
                    </div>
                    <div class="content">
                      {$contact.inner_street_address}
                    </div>
                    <div class="clear"></div>
                  </div>
                  <div class="crm-section city_Primary-section">
                    <div class="label">
                      City
                    </div>
                    <div class="content">
                      {$contact.inner_city}
                    </div>
                    <div class="clear"></div>
                  </div>
                  <div class="crm-section state_province_Primary-section">
                    <div class="label">
                      State
                    </div>
                    <div class="content">
                      {$contact.inner_state_province}, {$contact.inner_country}
                    </div>
                    <div class="clear"></div>
                  </div>
                  <div class="crm-section postal_code_Primary-section">
                    <div class="label">
                      Postal Code
                    </div>
                    <div class="content">
                      {$contact.inner_postal_code}
                    </div>
                    <div class="clear"></div>
                  </div>
                </div>
              </td><td>
                <div class="crm-summary-col-1">
                  <div class="crm-section email_Primary-section">
                    <div class="label">
                      Primary Email
                    </div>
                    <div class="content">
                      {$contact.inner_email}
                    </div>
                    <div class="clear"></div>
                  </div>
                  {*
                  <div class="crm-section group-section">
                    <div class="label">
                      Groups
                    </div>
                    <div class="content">

                    </div>
                    <div class="clear"></div>
                  </div>
                  
                  <div class="crm-section tag-section">
                    <div class="label">
                      Tags
                    </div>
                    <div class="content">

                    </div>
                    <div class="clear"></div>
                  </div>
                  *}
                  <div class="crm-section gender_id-section">
                    <div class="label">
                      Gender
                    </div>
                    <div class="content">
                      {$contact.inner_gender}
                    </div>
                    <div class="clear"></div>
                  </div>
                  <div class="crm-section birth_date-section">
                    <div class="label">
                      Date of Birth
                    </div>
                    <div class="content">
                      {$contact.inner_birth_date}
                    </div>
                    <div class="clear"></div>
                  </div>
                </div>
              </td></tr>
          </tbody></table>
      </div>
    </div>
  </div>
</a>
<a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$contact.contact_id`"}">
  {$contact.display_name}
</a>