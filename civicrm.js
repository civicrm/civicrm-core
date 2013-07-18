// http://civicrm.org/licensing
jQuery(function ($) {
  $('#crm-wp-insert-shortcode').on('click', function () {
    var form_id = $("#add_civicomponent_id").val();
    if (form_id == "") {
      alert('Please select a frontend element.');
      return;
    }

    var component = $("#add_civicomponent_id").val();
    var shortcode = '[civicrm component="' + component + '"';
    switch (component) {
      case 'contribution':
        shortcode += ' id="' + $("#add_contributepage_id").val() + '"';
        shortcode += ' mode="' + $("input[name='component_mode']:checked").val() + '"';
        break;
      case 'event':
        shortcode += ' id="' + $("#add_eventpage_id").val() + '"';
        shortcode += ' action="' + $("input[name='event_action']:checked").val() + '"';
        shortcode += ' mode="' + $("input[name='component_mode']:checked").val() + '"';
        break;
      case 'profile':
        shortcode += ' gid="' + $("#add_profilepage_id").val() + '"';
        shortcode += ' mode="' + $("input[name='profile_mode']:checked").val() + '"';
        break;
      case 'user-dashboard':
        break;
      case 'petition':
        shortcode += ' id="' + $("#add_petition_id").val() + '"';
        break;
    }
    shortcode += ']';
    window.send_to_editor(shortcode);
  });

  $('#add_civicomponent_id').on('change', function () {
    switch ($(this).val()) {
      case 'contribution':
        $('#contribution-section, #component-section').show();
        $('#profile-section, #profile-mode-section').hide();
        $('#event-section, #action-section-event').hide();
        $('#petition-section').hide();
        break;
      case 'event':
        $('#contribution-section').hide();
        $('#profile-section, #profile-mode-section').hide();
        $('#event-section, #component-section, #action-section-event').show();
        $('#petition-section').hide();
        break;
      case 'profile':
        $('#contribution-section, #component-section').hide();
        $('#profile-section, #profile-mode-section').show();
        $('#event-section, #action-section-event').hide();
        $('#petition-section').hide();
        break;
      case 'petition':
        $('#contribution-section, #component-section').hide();
        $('#profile-section, #profile-mode-section').hide();
        $('#event-section, #action-section-event').hide();
        $('#petition-section').show();
        break;
      default:
        $('#contribution-section, #event-section, #component-section, #action-section-event').hide();
        $('#profile-section, #profile-mode-section').hide();
        $('#petition-section').hide();
        break;
    }
  });
});
