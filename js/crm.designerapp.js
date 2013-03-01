cj(document).ready(function($) {
  /**
   * FIXME we depend on this being a global singleton, mainly to facilitate vents
   *
   * vents:
   * - resize: the size/position of widgets should be adjusted
   * - ufUnsaved: any part of a UFGroup was changed; args: (is_changed:bool)
   * - formOpened: a toggleable form (such as a UFFieldView or a UFGroupView) has been opened
   */
  CRM.designerApp = new Backbone.Marionette.Application();

});
