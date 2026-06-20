class ShimmyTagA extends HTMLElement {
  connectedCallback() {
    this.textContent = 'Hello world';
  }
}

customElements.define('shimmy-tag-a', TagA);
