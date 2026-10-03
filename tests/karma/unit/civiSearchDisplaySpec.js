'use strict';

describe('civi-search-display total count', function() {
  var container;

  beforeAll(function() {
    // The base class of Chart Kit's display is not registered on its own
    if (!window.customElements.get('test-civi-search-display')) {
      window.customElements.define('test-civi-search-display', class extends CRM.components.civi_search_display {});
    }
  });

  beforeEach(function() {
    jasmine.clock().install();
    CRM.tabHeader = {updateCount: jasmine.createSpy('updateCount')};
    spyOn(CRM, 'url').and.returnValue('/civicrm/ajax/api4');
    spyOn(CRM.$, 'post').and.callFake(function(url, data) {
      var response = data.calls ? {run: {values: [{}, {}], count: 2}} : {values: [], count: 7};
      return CRM.$.Deferred().resolve(response).promise();
    });
    container = document.createElement('div');
    document.body.appendChild(container);
  });

  afterEach(function() {
    delete CRM.tabHeader;
    container.remove();
    jasmine.clock().uninstall();
  });

  // Lets the API promise callbacks run after the count timer fires
  async function tickPastCountFetch() {
    jasmine.clock().tick(900);
    await Promise.resolve();
    await Promise.resolve();
  }

  function contactTab(html) {
    container.innerHTML = '<div class="crm-contact-page"><div class="ui-tabs-panel" id="contact-chart">' + html + '</div></div>';
    return container.querySelectorAll('test-civi-search-display');
  }

  var display = '<test-civi-search-display search="S" display="D"></test-civi-search-display>';

  it('updates the contact tab count from the first display in the tab', async function() {
    contactTab(display);
    await tickPastCountFetch();
    expect(CRM.tabHeader.updateCount).toHaveBeenCalledWith('#tab_chart', 7);
  });

  it('treats a display inside an Angular wrapper as the first display', async function() {
    contactTab('<crm-search-display-chart-kit search-name="S" display-name="D" search="S" display="D">' + display + '</crm-search-display-chart-kit>');
    await tickPastCountFetch();
    expect(CRM.tabHeader.updateCount).toHaveBeenCalledWith('#tab_chart', 7);
  });

  it('leaves the contact tab count to the first display', async function() {
    contactTab('<div search="S" display="First"></div>' + display);
    await tickPastCountFetch();
    expect(CRM.$.post).not.toHaveBeenCalled();
    expect(CRM.tabHeader.updateCount).not.toHaveBeenCalled();
  });

  it('leaves the contact tab count to the table holding it as a subsearch', async function() {
    contactTab('<div search="S" display="Table"><div class="crm-search-col-type-subsearch">' +
      '<crm-search-display-chart-kit search="S" display="D">' + display + '</crm-search-display-chart-kit></div></div>');
    await tickPastCountFetch();
    expect(CRM.$.post).not.toHaveBeenCalled();
    expect(CRM.tabHeader.updateCount).not.toHaveBeenCalled();
  });

  it('fetches the unfiltered count when afform filters narrow the loaded results', async function() {
    var element = contactTab('<test-civi-search-display search="S" display="D" filters="{&quot;contact_id&quot;:3}"></test-civi-search-display>')[0];
    element.results = [];
    spyOn(element, 'getAfformFilters').and.returnValue({status_id: 1});
    await tickPastCountFetch();
    expect(JSON.parse(CRM.$.post.calls.mostRecent().args[1].params).filters).toEqual({contact_id: 3});
    expect(CRM.tabHeader.updateCount).toHaveBeenCalledWith('#tab_chart', 7);
  });

  it('fetches no separate count when loaded results have no afform filter values', async function() {
    var element = contactTab(display)[0];
    element.results = [];
    spyOn(element, 'getAfformFilters').and.returnValue({status_id: undefined});
    await tickPastCountFetch();
    expect(CRM.$.post).not.toHaveBeenCalled();
  });

  it('fetches no count without a counter', async function() {
    container.innerHTML = display;
    await tickPastCountFetch();
    expect(CRM.$.post).not.toHaveBeenCalled();
  });

  it('reports the row count of a search run to onTotalCount callbacks', async function() {
    container.innerHTML = display;
    var element = container.querySelector('test-civi-search-display');
    var onTotalCount = jasmine.createSpy('onTotalCount');
    element.onTotalCount.push(onTotalCount);
    await element.runSearch();
    expect(onTotalCount).toHaveBeenCalledWith(2);
  });

});
