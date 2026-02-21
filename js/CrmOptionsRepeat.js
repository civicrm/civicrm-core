class CrmOptionsRepeat extends HTMLElement {
  constructor() {
    super();
    this.fieldMap = new Map();
  }

  connectedCallback() {
    // Ensure initialization happens after DOM is fully loaded
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.init());
    }
    else {
      this.init();
    }
  }

  init() {
    this.table = this.querySelector('table tbody');
    this.hiddenInput = this.querySelector(':scope > input[type="hidden"]');

    // Get the template row and store it
    this.templateRow = this.table.querySelector('tr').cloneNode(true);

    // Create field mapping from the template row
    const templateInputs = this.templateRow.querySelectorAll('input');
    templateInputs.forEach((input, index) => {
      const originalName = input.getAttribute('name');
      this.fieldMap.set(index, originalName);
      input.removeAttribute('name');
      input.removeAttribute('id');
      if (input.type === 'radio') {
        input.setAttribute('name', originalName + Math.random().toString(36).substring(2));
      }
    });

    // Initialize data from hidden input or create new array
    this.data = [];
    try {
      if (this.hiddenInput.value) {
        this.data = JSON.parse(this.hiddenInput.value);
      }
    } catch (e) {
    }

    // Clear existing rows
    this.table.innerHTML = '';

    // Add initial rows from data or at least one row
    if (this.data.length > 0) {
      this.data.forEach(rowData => this.addRow(rowData));
    } else {
      this.addRow();
    }

    // Add event listeners
    this.addEventListener('click', (e) => {
      const addButton = e.target.closest('.crm-options-repeat-add');
      if (addButton) {
        e.preventDefault();
        this.addRow();
      }
      const removeButton = e.target.closest('.crm-options-repeat-remove');
      if (removeButton) {
        e.preventDefault();
        this.removeRow(removeButton.closest('tr'));
      }
      const sortButton = e.target.closest('.crm-options-repeat-sort');
      if (sortButton) {
        e.preventDefault();
        this.sortByColumn(sortButton.closest('th'));
      }
    });

    // Listen for input changes
    this.addEventListener('input', () => this.updateData());

    // Add sortable
    if (this.templateRow.querySelector('.crm-draggable')) {
      CRM.$(this.table).sortable({
        handle: '.crm-draggable',
        containment: this,
        update: () => this.updateData(),
      });
    }
  }

  removeRow(row) {
    if (this.table.querySelectorAll('tr').length > 1) {
      row.remove();
      this.updateData();
    }
  }

  sortByColumn(sortHeader) {
    const columnIndex = Array.from(sortHeader.parentNode.children).indexOf(sortHeader);

    // Sort rows based on the clicked column's values
    const rows = Array.from(this.table.querySelectorAll('tr'));
    const sortedRows = rows.sort((a, b) => {
      const cellA = a.children[columnIndex]?.querySelector('input')?.value || '';
      const cellB = b.children[columnIndex]?.querySelector('input')?.value || '';

      // Sort numerically or alphabetically, if applicable
      if (!isNaN(cellA) && !isNaN(cellB)) {
        return Number(cellA) - Number(cellB);
      }
      return cellA.localeCompare(cellB, undefined, {numeric: true});
    });

    // Reattach sorted rows to the table
    this.table.innerHTML = '';
    sortedRows.forEach(row => this.table.appendChild(row));

    // Update data after sorting
    this.updateData();
  }

  addRow(data = {}) {
    const newRow = this.templateRow.cloneNode(true);

    // Set values if data exists
    newRow.querySelectorAll('input').forEach((input, index) => {
      const fieldName = this.fieldMap.get(index);

      if (data[fieldName] !== undefined) {
        if (input.type === 'checkbox' || input.type === 'radio') {
          input.checked = data[fieldName];
        } else {
          input.value = data[fieldName];
        }
      }
      else if (input.value && !isNaN(input.value)) {
        // Iterate numeric default value
        input.value = this.table.querySelectorAll('tr').length + (+input.value);
      }
    });

    this.table.appendChild(newRow);
    this.updateData();
  }

  updateData() {
    const rows = Array.from(this.table.querySelectorAll('tr'));
    this.data = rows.map(row => {
      const rowData = {};
      row.querySelectorAll('input').forEach((input, index) => {
        const fieldName = this.fieldMap.get(index);
        if (input.type === 'checkbox' || input.type === 'radio') {
          rowData[fieldName] = input.checked;
        } else {
          rowData[fieldName] = input.value;
        }
      });
      return rowData;
    });
    this.hiddenInput.value = JSON.stringify(this.data);
  }
}

// Register the custom element
customElements.define('crm-options-repeat', CrmOptionsRepeat);
