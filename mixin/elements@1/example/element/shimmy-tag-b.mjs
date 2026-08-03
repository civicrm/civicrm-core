class ShimmyTagB extends HTMLElement {
  connectedCallback() {
    this.textContent = 'Hello world';
  }
}

customElements.define('shimmy-tag-b', TagA);
