(function (CRM) {

  /**
   * <civi-activity-contacts activity-id="1234" type="Source Contact" loading-placeholders="3">
   *
   */
  class CiviActivityContacts extends HTMLElement {

    /* jshint ignore: start */
    static observedAttributes = ['activity-id'];
    /* jshint ignore: end */

    connectedCallback() {
      this.fetchAndRender();
    }

    /**
     * Rerender when activity-id is updated
     *
     * @param  {String} name     The attribute name
     */
    attributeChangedCallback (name) {
      if (name === 'activity-id' && this.activityId) {
        this.fetchAndRender();
      }
    }

    get activityId() {
      const val = parseInt(this.getAttribute('activity-id'));
      return val ? val : null;
    }

    get type() {
      return this.getAttribute('type');
    }

    get loadingPlaceholders() {
      const val = parseInt(this.getAttribute('loading-placeholders'));
      return val ? val : null;
    }

    async fetchAndRender() {
      this.renderLoading();
      if (!this.activityId) {
        return;
      }
      const contacts = await this.fetchData();
      this.render(contacts);
    }

    async fetchData() {
      const records = await CRM.api4('ActivityContact', 'get', {
        where: [
          ['activity_id', '=', this.activityId],
          ['record_type_id:name', '=', this.type],
        ],
        select: [
          'contact_id',
          'contact_id.display_name'
        ],
        orderBy: {
          'contact_id.sort_name': 'ASC'
        },
        // limit is 11 - display up to 10 and show ... if 11th exists
        limit: 11
      });

      return records.map((record) => ({
        id: record.contact_id,
        display_name: record['contact_id.display_name']
      }));
    }

    renderLoading() {
      const placeholder = '<li><span class="crm-search-loading-placeholder" /></li>';
      this.innerHTML = `<ul>${placeholder.repeat(this.loadingPlaceholders)}</ul>`;
    }

    render(contacts) {
      if (!contacts.length) {
        this.innerText = ts('(none)');
        return;
      }

      this.innerHTML = `<ul></ul>`;

      this.querySelector('ul').append(...contacts.map((record, i) => {
        if (i === 10) {
          // if there are more than 10 contacts, show ... as the last item
          const li = document.createElement('li');
          li.innerText = '...';
          return li;
        }

        const anchor = document.createElement('a');
        anchor.href = CRM.url(`civicrm/contact/view?reset=1&cid=${record.id}`);
        anchor.target = '_blank';
        anchor.innerText = record.display_name;

        const li = document.createElement('li');
        li.append(anchor);
        return li;
      }));
    }
  }

  // register custom element in our civi namespace
  customElements.define('civi-activity-contacts', CiviActivityContacts);

})(CRM);
