/**
 * Universal Autocomplete Component for Search Fields
 * Provides real-time suggestions as user types
 */

class AutocompleteSearch {
    constructor(inputElement, options = {}) {
        this.input = inputElement;
        this.options = {
            minLength: 1,
            delay: 300,
            maxSuggestions: 10,
            endpoint: null,
            dataField: 'data',
            displayField: 'name',
            valueField: 'id',
            onSelect: null,
            ...options
        };
        
        this.suggestionsContainer = null;
        this.currentSuggestions = [];
        this.selectedIndex = -1;
        this.isOpen = false;
        this.timeoutId = null;
        
        this.init();
    }
    
    init() {
        // Create suggestions container
        this.createSuggestionsContainer();
        
        // Add event listeners
        this.input.addEventListener('input', this.handleInput.bind(this));
        this.input.addEventListener('keydown', this.handleKeydown.bind(this));
        this.input.addEventListener('blur', this.handleBlur.bind(this));
        this.input.addEventListener('focus', this.handleFocus.bind(this));
        
        // Add CSS class for styling
        this.input.classList.add('autocomplete-input');
    }
    
    createSuggestionsContainer() {
        this.suggestionsContainer = document.createElement('div');
        this.suggestionsContainer.className = 'autocomplete-suggestions';
        this.suggestionsContainer.style.display = 'none';
        
        // Insert after the input element
        this.input.parentNode.insertBefore(this.suggestionsContainer, this.input.nextSibling);
    }
    
    handleInput(event) {
        const query = event.target.value.trim();
        
        // Clear previous timeout
        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
        }
        
        // Hide suggestions if query is too short
        if (query.length < this.options.minLength) {
            this.hideSuggestions();
            return;
        }
        
        // Debounce the search
        this.timeoutId = setTimeout(() => {
            this.search(query);
        }, this.options.delay);
    }
    
    handleKeydown(event) {
        if (!this.isOpen) return;
        
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.selectNext();
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.selectPrevious();
                break;
            case 'Enter':
                event.preventDefault();
                this.selectCurrent();
                break;
            case 'Escape':
                this.hideSuggestions();
                break;
        }
    }
    
    handleBlur(event) {
        // Delay hiding to allow clicking on suggestions
        setTimeout(() => {
            if (!this.suggestionsContainer.contains(document.activeElement)) {
                this.hideSuggestions();
            }
        }, 150);
    }
    
    handleFocus(event) {
        const query = event.target.value.trim();
        if (query.length >= this.options.minLength) {
            this.search(query);
        }
    }
    
    async search(query) {
        try {
            if (this.options.endpoint) {
                // Fetch suggestions from server
                const response = await fetch(this.options.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=autocomplete&query=${encodeURIComponent(query)}&context=${encodeURIComponent(this.options.context || 'general')}`
                });
                
                const data = await response.json();
                if (data.success) {
                    this.showSuggestions(data[this.options.dataField] || []);
                }
            } else {
                // Use static data
                const suggestions = this.filterSuggestions(query);
                this.showSuggestions(suggestions);
            }
        } catch (error) {
            console.error('Autocomplete search error:', error);
        }
    }
    
    filterSuggestions(query) {
        if (!this.options.staticData) return [];
        
        const queryLower = query.toLowerCase();
        return this.options.staticData.filter(item => {
            const displayValue = this.getDisplayValue(item).toLowerCase();
            return displayValue.includes(queryLower);
        }).slice(0, this.options.maxSuggestions);
    }
    
    showSuggestions(suggestions) {
        this.currentSuggestions = suggestions;
        this.selectedIndex = -1;
        
        if (suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        // Clear previous suggestions
        this.suggestionsContainer.innerHTML = '';
        const isSearchInputTarget = this.input && this.input.id === 'searchInput';
        
        // Add suggestions
        suggestions.forEach((suggestion, index) => {
            const item = document.createElement('div');
            const displayText = this.getDisplayValue(suggestion);
            const valueText = this.getValue(suggestion) || displayText;
            
            item.className = 'autocomplete-suggestion suggestion-item';
            item.textContent = displayText;
            item.dataset.index = index;
            item.dataset.value = valueText;
            
            if (isSearchInputTarget) {
                item.addEventListener('click', () => {
                    const targetInput = document.getElementById('searchInput') || this.input;
                    if (targetInput) {
                        targetInput.value = valueText;
                        targetInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    this.hideSuggestions();
                    if (this.options.onSelect) {
                        this.options.onSelect(suggestion);
                    }
                    if (typeof window.searchTable === 'function') {
                        window.searchTable();
                    }
                });
            } else {
                item.addEventListener('click', () => {
                    this.selectSuggestion(suggestion);
                });
            }
            
            this.suggestionsContainer.appendChild(item);
        });
        
        this.showSuggestionsContainer();
    }
    
    showSuggestionsContainer() {
        this.suggestionsContainer.style.display = 'block';
        this.isOpen = true;
        
        // Position the container
        const inputRect = this.input.getBoundingClientRect();
        const containerRect = this.input.parentNode.getBoundingClientRect();
        
        this.suggestionsContainer.style.position = 'absolute';
        this.suggestionsContainer.style.top = `${inputRect.bottom - containerRect.top}px`;
        this.suggestionsContainer.style.left = `${inputRect.left - containerRect.left}px`;
        this.suggestionsContainer.style.width = `${inputRect.width}px`;
        this.suggestionsContainer.style.zIndex = '1000';
    }
    
    hideSuggestions() {
        this.suggestionsContainer.style.display = 'none';
        this.isOpen = false;
        this.selectedIndex = -1;
    }
    
    selectNext() {
        if (this.selectedIndex < this.currentSuggestions.length - 1) {
            this.selectedIndex++;
            this.updateSelection();
        }
    }
    
    selectPrevious() {
        if (this.selectedIndex > 0) {
            this.selectedIndex--;
            this.updateSelection();
        }
    }
    
    selectCurrent() {
        if (this.selectedIndex >= 0 && this.selectedIndex < this.currentSuggestions.length) {
            this.selectSuggestion(this.currentSuggestions[this.selectedIndex]);
        }
    }
    
    selectSuggestion(suggestion) {
        this.input.value = this.getDisplayValue(suggestion);
        this.hideSuggestions();
        
        if (this.options.onSelect) {
            this.options.onSelect(suggestion);
        }
        
        // Trigger change event
        this.input.dispatchEvent(new Event('change', { bubbles: true }));
    }
    
    updateSelection() {
        const items = this.suggestionsContainer.querySelectorAll('.autocomplete-suggestion');
        items.forEach((item, index) => {
            item.classList.toggle('selected', index === this.selectedIndex);
        });
    }
    
    getDisplayValue(suggestion) {
        if (typeof suggestion === 'string') {
            return suggestion;
        }
        return suggestion[this.options.displayField] || suggestion.name || suggestion.text || '';
    }
    
    getValue(suggestion) {
        if (typeof suggestion === 'string') {
            return suggestion;
        }
        return suggestion[this.options.valueField] || suggestion.id || suggestion.value || '';
    }
}

// CSS Styles for autocomplete
const autocompleteCSS = `
.autocomplete-input {
    position: relative;
}

.autocomplete-suggestions {
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 4px 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    max-height: 200px;
    overflow-y: auto;
    position: absolute;
    z-index: 1000;
}

.autocomplete-suggestion {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}

.autocomplete-suggestion:last-child {
    border-bottom: none;
}

.autocomplete-suggestion:hover,
.autocomplete-suggestion.selected {
    background-color: #f8f9fa;
}

.autocomplete-suggestion.selected {
    background-color: #007bff;
    color: white;
}
`;

// Inject CSS
const style = document.createElement('style');
style.textContent = autocompleteCSS;
document.head.appendChild(style);

// Export for use in other scripts
window.AutocompleteSearch = AutocompleteSearch;
