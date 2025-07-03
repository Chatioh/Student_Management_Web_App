// Admin Dashboard JavaScript

document.addEventListener("DOMContentLoaded", function() {
    const sidebar = document.getElementById("sidebar");
    const content = document.getElementById("content");
    const sidebarCollapse = document.getElementById("sidebarCollapse");
    const sidebarCollapseTop = document.getElementById("sidebarCollapseTop");

    function toggleSidebar() {
        sidebar.classList.toggle("active");
        content.classList.toggle("active");
    }

    if (sidebarCollapse) {
        sidebarCollapse.addEventListener("click", toggleSidebar);
    }

    if (sidebarCollapseTop) {
        sidebarCollapseTop.addEventListener("click", toggleSidebar);
    }

    // Close sidebar on smaller screens when a menu item is clicked
    document.querySelectorAll("#sidebar .components li a").forEach(item => {
        item.addEventListener("click", () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove("active");
                content.classList.remove("active");
            }
        });
    });

    // Adjust sidebar on window resize
    window.addEventListener("resize", () => {
        if (window.innerWidth > 768) {
            sidebar.classList.remove("active");
            content.classList.remove("active");
        }
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll("[data-bs-toggle=\"tooltip\"]"));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll("[data-bs-toggle=\"popover\"]"));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});
// Notification functions
function showSuccessMessage(message) {
    const modal = new bootstrap.Modal(document.getElementById('successModal'));
    document.getElementById('successMessage').textContent = message;
    modal.show();
}

function showErrorMessage(message) {
    const modal = new bootstrap.Modal(document.getElementById('errorModal'));
    document.getElementById('errorMessage').textContent = message;
    modal.show();
}

function showConfirmDialog(message, callback) {
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    document.getElementById('confirmMessage').textContent = message;
    
    const confirmButton = document.getElementById('confirmAction');
    confirmButton.onclick = function() {
        modal.hide();
        if (callback) callback();
    };
    
    modal.show();
}

// CRUD Operations
class CrudManager {
    constructor(entityName, apiEndpoint) {
        this.entityName = entityName;
        this.apiEndpoint = apiEndpoint;
    }

    async create(data) {
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                showSuccessMessage(`${this.entityName} created successfully!`);
                return result;
            } else {
                showErrorMessage(result.message || `Failed to create ${this.entityName}`);
                return null;
            }
        } catch (error) {
            showErrorMessage(`Error creating ${this.entityName}: ${error.message}`);
            return null;
        }
    }

    async update(id, data) {
        try {
            const response = await fetch(`${this.apiEndpoint}/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                showSuccessMessage(`${this.entityName} updated successfully!`);
                return result;
            } else {
                showErrorMessage(result.message || `Failed to update ${this.entityName}`);
                return null;
            }
        } catch (error) {
            showErrorMessage(`Error updating ${this.entityName}: ${error.message}`);
            return null;
        }
    }

    async delete(id) {
        return new Promise((resolve) => {
            showConfirmDialog(`Are you sure you want to delete this ${this.entityName}?`, async () => {
                try {
                    const response = await fetch(`${this.apiEndpoint}/${id}`, {
                        method: 'DELETE'
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showSuccessMessage(`${this.entityName} deleted successfully!`);
                        resolve(result);
                    } else {
                        showErrorMessage(result.message || `Failed to delete ${this.entityName}`);
                        resolve(null);
                    }
                } catch (error) {
                    showErrorMessage(`Error deleting ${this.entityName}: ${error.message}`);
                    resolve(null);
                }
            });
        });
    }

    async fetch(params = {}) {
        try {
            const queryString = new URLSearchParams(params).toString();
            const url = queryString ? `${this.apiEndpoint}?${queryString}` : this.apiEndpoint;
            
            const response = await fetch(url);
            const result = await response.json();
            
            if (result.success) {
                return result.data;
            } else {
                showErrorMessage(result.message || `Failed to fetch ${this.entityName}`);
                return null;
            }
        } catch (error) {
            showErrorMessage(`Error fetching ${this.entityName}: ${error.message}`);
            return null;
        }
    }
}

// Search and Filter functionality
function initializeSearch(tableId, searchInputId) {
    const searchInput = document.getElementById(searchInputId);
    const table = document.getElementById(tableId);
    
    if (searchInput && table) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
}

// Pagination functionality
function initializePagination(tableId, paginationId, itemsPerPage = 10) {
    const table = document.getElementById(tableId);
    const pagination = document.getElementById(paginationId);
    
    if (!table || !pagination) return;
    
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const totalPages = Math.ceil(rows.length / itemsPerPage);
    let currentPage = 1;
    
    function showPage(page) {
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        
        rows.forEach((row, index) => {
            if (index >= start && index < end) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        updatePaginationButtons(page);
    }
    
    function updatePaginationButtons(page) {
        pagination.innerHTML = '';
        
        // Previous button
        const prevButton = createPaginationButton('Previous', page > 1, () => {
            if (page > 1) {
                currentPage = page - 1;
                showPage(currentPage);
            }
        });
        pagination.appendChild(prevButton);
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            const button = createPaginationButton(i, true, () => {
                currentPage = i;
                showPage(currentPage);
            }, i === page);
            pagination.appendChild(button);
        }
        
        // Next button
        const nextButton = createPaginationButton('Next', page < totalPages, () => {
            if (page < totalPages) {
                currentPage = page + 1;
                showPage(currentPage);
            }
        });
        pagination.appendChild(nextButton);
    }
    
    function createPaginationButton(text, enabled, onClick, active = false) {
        const li = document.createElement('li');
        li.className = `page-item ${!enabled ? 'disabled' : ''} ${active ? 'active' : ''}`;
        
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.textContent = text;
        
        if (enabled) {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                onClick();
            });
        }
        
        li.appendChild(a);
        return li;
    }
    
    // Initialize
    showPage(1);
}

// Form validation
function validateForm(formId, rules) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    let isValid = true;
    
    Object.keys(rules).forEach(fieldName => {
        const field = form.querySelector(`[name="${fieldName}"]`);
        const rule = rules[fieldName];
        
        if (field) {
            // Remove previous error styling
            field.classList.remove('is-invalid');
            const errorDiv = field.parentNode.querySelector('.invalid-feedback');
            if (errorDiv) errorDiv.remove();
            
            // Validate field
            let fieldValid = true;
            let errorMessage = '';
            
            if (rule.required && !field.value.trim()) {
                fieldValid = false;
                errorMessage = `${fieldName} is required`;
            } else if (rule.minLength && field.value.length < rule.minLength) {
                fieldValid = false;
                errorMessage = `${fieldName} must be at least ${rule.minLength} characters`;
            } else if (rule.maxLength && field.value.length > rule.maxLength) {
                fieldValid = false;
                errorMessage = `${fieldName} must not exceed ${rule.maxLength} characters`;
            } else if (rule.pattern && !rule.pattern.test(field.value)) {
                fieldValid = false;
                errorMessage = rule.message || `${fieldName} format is invalid`;
            }
            
            if (!fieldValid) {
                isValid = false;
                field.classList.add('is-invalid');
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                errorDiv.textContent = errorMessage;
                field.parentNode.appendChild(errorDiv);
            }
        }
    });
    
    return isValid;
}

// Export functions for global use
window.CrudManager = CrudManager;
window.showSuccessMessage = showSuccessMessage;
window.showErrorMessage = showErrorMessage;
window.showConfirmDialog = showConfirmDialog;
window.initializeSearch = initializeSearch;
window.initializePagination = initializePagination;
window.validateForm = validateForm;

