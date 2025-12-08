/**
 * resources.js
 * Handles loading resources from database and managing resource display
 */

/**
 * Load resources from database via PHP API
 */
async function loadResourcesFromDatabase() {
    try {
        const response = await fetch('php/get_resources_by_category.php');
        const data = await response.json();
        
        if (data.success) {
            return data.resources;
        } else {
            console.error('Error fetching resources:', data.message);
            return getDefaultResources();
        }
    } catch (error) {
        console.error('Error loading resources from database:', error);
        // Fall back to default resources if database fails
        return getDefaultResources();
    }
}

/**
 * Default fallback resources in case database connection fails
 */
function getDefaultResources() {
    return {
        chemicals: [
            { id: 1, name: 'Sodium Chloride', quantity: 50, description: 'NaCl - Common salt' },
            { id: 2, name: 'Hydrochloric Acid', quantity: 30, description: 'HCl - Strong acid' },
            { id: 3, name: 'Sodium Hydroxide', quantity: 25, description: 'NaOH - Base compound' }
        ],
        equipment: [
            { id: 4, name: 'Microscope', quantity: 10, description: 'Laboratory microscope' },
            { id: 5, name: 'Centrifuge', quantity: 5, description: 'Laboratory centrifuge' },
            { id: 6, name: 'Hot Plate', quantity: 8, description: 'Electric hot plate' }
        ],
        glassware: [
            { id: 7, name: 'Beaker 250ml', quantity: 20, description: '250ml glass beaker' },
            { id: 8, name: 'Test Tube', quantity: 100, description: 'Standard test tube' },
            { id: 9, name: 'Flask 500ml', quantity: 15, description: '500ml Erlenmeyer flask' }
        ]
    };
}

/**
 * Render a resource category
 */
function renderResources(type, resources) {
    const grid = document.getElementById(type + 'Grid');
    if (!grid) return;
    
    grid.innerHTML = resources.map(resource => `
        <div class="resource-card">
            <div class="resource-name">${escapeHtml(resource.name)}</div>
            <div class="resource-info">${escapeHtml(resource.description || '')}</div>
            <div class="resource-quantity">Available: ${resource.quantity}</div>
            <button class="add-to-cart-btn" onclick="addToCart(${resource.id}, '${escapeHtml(resource.name)}', '${type}', ${resource.quantity})" 
                    ${resource.quantity === 0 ? 'disabled' : ''}>
                Select
            </button>
        </div>
    `).join('');
}

/**
 * Escape HTML special characters for security
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
