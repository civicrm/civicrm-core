(function ($, _) {
  $(function () {
    /**
     * FIXME we depend on this being a global singleton, mainly to facilitate vents
     *
     * vents:
     * - resize: the size/position of widgets should be adjusted
     * - ufUnsaved: any part of a UFGroup was changed; args: (is_changed:bool)
     * - formOpened: a toggleable form (such as a UFFieldView or a UFGroupView) has been opened
     */
    CRM.designerApp = new Backbone.Marionette.Application();

    /**
     * FIXME: Workaround for problem that having more than one instance
     * of a profile on the page will result in duplicate DOM ids.
     * @see CRM-12188
     */
    CRM.designerApp.clearPreviewArea = function () {
      $('.crm-profile-selector-preview-pane > *').each(function () {
        var parent = $(this).parent();
        CRM.designerApp.DetachedProfiles.push({
          parent: parent,
          item: $(this).detach()
        });
      });
    };
    CRM.designerApp.restorePreviewArea = function () {
      $.each(CRM.designerApp.DetachedProfiles, function () {
        $(this.parent).append(this.item);
      });
    };
  });
})(CRM.$, CRM._);
