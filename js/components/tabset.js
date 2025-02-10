(function() {
   /**
    * <civi-tabset>
    *
    * Expected markup is:
    *
    * ```
    * <civi-tabset>
    *   <details>
    *     <summary>Tab 1 Title</summary>
    *
    *     [Tab 1 Content]
    *
    *   </details>
    *   <details>
    *     <summary>Tab 2 Title</summary>
    *
    *      [Tab 2 Content]
    *
    *   </details>
    * </civi-tabset>
    * ```
    *
    * Implementation takes inspiration from https://daverupert.com/2021/10/native-html-tabs/
    *
    */
  class CiviTabset extends HTMLElement {
    constructor() {
      super();

      // bind class methods to the instance
      this.updateNav = this.updateNav.bind(this);

      this.openTab = this.openTab.bind(this);
      this.openTabById = this.openTabById.bind(this);

      this.tabToNav = {};
    }

    connectedCallback() {
      this.classList.add('ui-tabs')

      // initialise the nav header element
      this.nav = document.createElement('ul');
      this.nav.classList.add('ui-tabs-nav');
      this.nav.role = 'tablist';
      this.prepend(this.nav);

      // initial nav based on children at creation
      this.updateNav();

      // add observer to update nav if child elements change
      this.navSync = new MutationObserver(this.updateNav);

      this.navSync.observe(this, {
        childList: true
      });
    }

    disconnectedCallback() {
      this.navSync.disconnect();
    }

    get tabs() {
      return Array.from(this.children).filter((child) => (child.tagName === 'DETAILS'));
    }

    get tabHeaderItems() {
      return Array.from(this.nav.children);
    }

    // update nav header when tabs are added or taken away
    updateNav() {
      this.nav.innerHTML = '';

      this.tabs.forEach((tab) => {
        tab.role = 'tabpanel';

        const tabTitle = Array.from(tab.children).find((child) => (child.tagName === 'SUMMARY'));
        tabTitle.style.display = 'none';

        const navItem = document.createElement('li');
        this.tabToNav[tab] = navItem;

        const navAnchor = document.createElement('a');
        navAnchor.classList.add('crm-tabs-anchor');
        navAnchor.innerHTML = tabTitle.innerHTML;

        navAnchor.addEventListener('click', (e) => {
            e.preventDefault();
            this.openTab(tab);
        });

        navItem.append(navAnchor);
        this.nav.append(navItem);
      });

      // if this tab is marked open in html, trigger an open to ensure the corresponding
      // nav menu item is active and all other tabs are closed
      const firstOpenTab = this.tabs.find((tab) => tab.open);
      if (firstOpenTab) {
          this.openTab(firstOpenTab)
      }
    }

    openTab(tabToOpen) {
      // only this tab should be open
      this.tabs.forEach((tabToOpenOrClose) => tabToOpenOrClose.open = (tabToOpenOrClose === tabToOpen));

      const navItemOpen = this.tabToNav[tabToOpen];
      // only this nav item should be active
      this.tabHeaderItems.forEach((navItemOpenOrClosed) => navItemOpenOrClosed.classList.toggle('ui-tabs-selected', (navItemOpenOrClosed === navItemOpen)));
    }

    openTabById(tabId) {
      const tabToOpen = this.tabs.find((tab) => (tab.id === tabId));
      this.openTab(tabToOpen);
    }
  }

  // register custom element in our civi namespace
  customElements.define('civi-tabset', CiviTabset);
})();
