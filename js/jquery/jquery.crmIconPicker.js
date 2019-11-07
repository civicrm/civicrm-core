// https://civicrm.org/licensing
(function($, _) {
  "use strict";
  /* jshint validthis: true */
  var icons = [], loaded;

  $.fn.crmIconPicker = function() {

    function loadIcons() {
      if (!loaded) {
        loaded = $.Deferred();
        CRM.$.get(CRM.config.resourceBase + 'bower_components/font-awesome/css/font-awesome.css').done(function(data) {
          var match,
            regex = /\.(fa-[-a-zA-Z0-9]+):before {/g;
          while((match = regex.exec(data)) !== null) {
            icons.push(match[1]);
          }
          loaded.resolve();
        });
      }
      return loaded;
    }

    return this.each(function() {
      if ($(this).hasClass('iconpicker-widget')) {
        return;
      }

      var $input = $(this),
        $button = $('<a class="crm-icon-picker-button" href="#" />').button().removeClass('ui-corner-all').attr('title', $input.attr('title')),
        $style = $('<select class="crm-form-select"></select>'),
        options = [
          {key: 'fa-rotate-90', value: ts('Rotate right')},
          {key: 'fa-rotate-270', value: ts('Rotate left')},
          {key: 'fa-rotate-180', value: ts('Rotate 180')},
          {key: 'fa-flip-horizontal', value: ts('Flip horizontal')},
          {key: 'fa-flip-vertical', value: ts('Flip vertical')}
        ];

      function formatButton() {
        var val = $input.val().replace('fa ', '');
        val = val.replace('crm-i ', '');
        var split = val.split(' ');
        $button.button('option', {
          label: split[0] || ts('None'),
          icons: {primary: val ? val : 'fa-'}
        });
        $style.toggle(!!split[0]).val(split[1] || '');
      }

      $input.hide().addClass('iconpicker-widget').after($style).after('&nbsp;').after($button).change(formatButton);

      CRM.utils.setOptions($style, options, ts('Normal'));

      formatButton();

      $style.change(function() {
        if ($input.val()) {
          var split = $input.val().split(' '),
            style = $style.val();
          $input.val(split[0] + (style ? ' ' + style : '')).change();
        }
      });

      $button.click(function(e) {
        var dialog;

        function displayIcons() {
          var term = $('input[name=search]', dialog).val().replace(/-/g, '').toLowerCase(),
            $place = $('div.icons', dialog).html('');
          $.each(icons, function(i, icon) {
            if (!term.length || icon.replace(/-/g, '').indexOf(term) > -1) {
              var item = $('<a href="#" title="' + icon + '"/>').button({
                icons: {primary: icon + ' ' + $style.val()}
              });
              $place.append(item);
            }
          });
        }

        function displayDialog() {
          dialog.append('<style type="text/css">' +
            '#crmIconPicker {font-size: 20px;}' +
            '#crmIconPicker .icon-ctrls input {font-family: FontAwesome; padding-left: .5em; margin-bottom: 1em;}' +
            '#crmIconPicker .icon-ctrls > * {display: inline-block; vertical-align: top; margin-right: 1em;}' +
            '#crmIconPicker .icon-ctrls > button {float: right; margin-right: 0;}' +
            '#crmIconPicker a.ui-button {width: 1em; height: 1em; color: #222;}' +
            '#crmIconPicker a.ui-button .ui-icon {margin-top: -0.5em; width: auto; height: auto;}' +
            '</style>' +
            '<div class="icon-ctrls crm-clearfix">' +
            '<input class="crm-form-text" name="search" placeholder="&#xf002"/>' +
            '<select class="crm-form-select"></select>' +
            '<button type="button" class="cancel" title=""><i class="crm-i fa-ban"></i> ' + ts('No icon') + '</button>' +
            '</div>' +
            '<div class="icons"></div>'
          );
          var $styleSelect = $('.icon-ctrls select', dialog);
          CRM.utils.setOptions($styleSelect, options, ts('Normal'));
          $styleSelect.val($style.val());
          $styleSelect.change(function() {
            $style.val($styleSelect.val());
            displayIcons();
          });
          $('.icon-ctrls button', dialog).click(pickIcon);
          displayIcons();
          dialog.unblock();
        }

        function pickIcon(e) {
          var newIcon = $(this).attr('title'),
            style = newIcon ? $style.val() : '';
          $input.val(newIcon + (style ? ' ' + style : '')).change();
          dialog.dialog('close');
          e.preventDefault();
        }

        dialog = $('<div id="crmIconPicker"/>').dialog({
          title: $input.attr('title'),
          width: '80%',
          height: '90%',
          modal: true
        }).block()
          .on('click', 'a', pickIcon)
          .on('keyup', 'input[name=search]', displayIcons)
          .on('dialogclose', function() {
            $(this).dialog('destroy').remove();
          });
        loadIcons().done(displayDialog);
        e.preventDefault();
      });

    });
  };

  $(document)
    .on('crmLoad', function(e) {
      $('.crm-icon-picker', e.target).not('.iconpicker-widget').crmIconPicker();
    });
}(CRM.$, CRM._));
