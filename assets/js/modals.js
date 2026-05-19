/**
 * MODALS PROFESSIONNELS - STORE SUITE
 * Système de modals modernes pour confirmations et alertes
 */

// Styles pour les modals modernes
const modalStyles = `
<style>
.modern-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 2147483647; /* Toujours au-dessus de Bootstrap */
    animation: fadeIn 0.2s ease;
}

.modern-modal-overlay.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modern-modal {
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 500px;
    width: 90%;
    animation: slideUp 0.3s ease;
    overflow: hidden;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modern-modal-header {
    padding: 24px 24px 16px;
    border-bottom: 1px solid #e9ecef;
}

.modern-modal-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 28px;
}

.modern-modal-icon.success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.modern-modal-icon.error {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.modern-modal-icon.danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.modern-modal-icon.warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.modern-modal-icon.info {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.modern-modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    text-align: center;
    margin: 0 0 8px;
    color: #1e293b;
}

.modern-modal-message {
    text-align: center;
    color: #64748b;
    font-size: 0.95rem;
    line-height: 1.6;
    margin: 0;
}

.modern-modal-body {
    padding: 20px 24px;
}

.modern-modal-footer {
    padding: 16px 24px 24px;
    display: flex;
    gap: 12px;
    justify-content: center;
}

.modern-modal-btn {
    padding: 12px 28px;
    border-radius: 8px;
    border: none;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.modern-modal-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.modern-modal-btn-primary {
    background: linear-gradient(135deg, var(--couleur-primaire, #206bc4), var(--couleur-secondaire, #1a5aa8));
    color: white;
}

.modern-modal-btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.modern-modal-btn-error {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.modern-modal-btn-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.modern-modal-btn-secondary {
    background: #f1f5f9;
    color: #64748b;
}

.modern-modal-btn-secondary:hover {
    background: #e2e8f0;
}
</style>
`;

// Injecter les styles
if (!document.getElementById('modern-modal-styles')) {
    document.head.insertAdjacentHTML('beforeend', modalStyles);
    const styleTag = document.head.lastElementChild;
    styleTag.id = 'modern-modal-styles';
}

/**
 * Afficher un modal de confirmation moderne
 * @param {Object} options - Options du modal
 * @returns {Promise<boolean>} - true si confirmé, false si annulé
 */
window.showConfirmModal = function(options = {}) {
    const {
        title = 'Confirmation',
        message = 'Êtes-vous sûr de vouloir continuer ?',
        icon = 'warning',
        confirmText = 'Confirmer',
        cancelText = 'Annuler',
        type = 'warning',
        onConfirm = null,
        onCancel = null
    } = options;

    return new Promise((resolve) => {
        // Icônes selon le type
        const icons = {
            success: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>',
            danger: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
            warning: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v2m0 4v.01"/><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/></svg>',
            info: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>'
        };

        const modalId = 'modal-confirm-' + Date.now();
        const modalHTML = `
            <div class="modern-modal-overlay" id="${modalId}">
                <div class="modern-modal">
                    <div class="modern-modal-header">
                        <div class="modern-modal-icon ${type}">
                            ${icons[icon] || icons.warning}
                        </div>
                        <h3 class="modern-modal-title">${title}</h3>
                        <p class="modern-modal-message">${message}</p>
                    </div>
                    <div class="modern-modal-footer">
                        <button type="button" class="modern-modal-btn modern-modal-btn-secondary" data-action="cancel">
                            ${cancelText}
                        </button>
                        <button type="button" class="modern-modal-btn modern-modal-btn-${type}" data-action="confirm">
                            ${confirmText}
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modalElement = document.getElementById(modalId);
        
        // Nettoyer les backdrops Bootstrap pour éviter les conflits d'empilement
        try {
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
        } catch (e) {}
        
        // Afficher le modal
        setTimeout(() => modalElement.classList.add('active'), 10);

        // Gestionnaires d'événements
        const handleResponse = (confirmed) => {
            modalElement.classList.remove('active');
            setTimeout(() => modalElement.remove(), 200);
            
            // Support pour les callbacks
            if (confirmed && typeof onConfirm === 'function') {
                onConfirm();
            } else if (!confirmed && typeof onCancel === 'function') {
                onCancel();
            }
            
            resolve(confirmed);
        };

        modalElement.querySelector('[data-action="confirm"]').addEventListener('click', () => handleResponse(true));
        modalElement.querySelector('[data-action="cancel"]').addEventListener('click', () => handleResponse(false));
        modalElement.addEventListener('click', (e) => {
            if (e.target === modalElement) handleResponse(false);
        });
    });
};

/**
 * Afficher un modal d'alerte/succès
 * @param {Object} options - Options du modal
 * @returns {Promise<void>}
 */
window.showAlertModal = function(options = {}) {
    return new Promise((resolve) => {
        const {
            title = 'Information',
            message = '',
            icon = 'info',
            buttonText = 'OK',
            type = 'info'
        } = options;

        const icons = {
            success: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>',
            error: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
            danger: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
            warning: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v2m0 4v.01"/><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/></svg>',
            info: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>'
        };

        const modalId = 'modal-alert-' + Date.now();
        const modalHTML = `
            <div class="modern-modal-overlay" id="${modalId}">
                <div class="modern-modal">
                    <div class="modern-modal-header">
                        <div class="modern-modal-icon ${type}">
                            ${icons[icon] || icons.info}
                        </div>
                        <h3 class="modern-modal-title">${title}</h3>
                        <p class="modern-modal-message">${message}</p>
                    </div>
                    <div class="modern-modal-footer">
                        <button type="button" class="modern-modal-btn modern-modal-btn-${type}" data-action="ok">
                            ${buttonText}
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modalElement = document.getElementById(modalId);
        // Nettoyer les backdrops Bootstrap pour éviter les conflits d'empilement
        try {
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
        } catch (e) {}
        
        setTimeout(() => modalElement.classList.add('active'), 10);

        const handleClose = () => {
            modalElement.classList.remove('active');
            setTimeout(() => modalElement.remove(), 200);
            resolve();
        };

        modalElement.querySelector('[data-action="ok"]').addEventListener('click', handleClose);
        modalElement.addEventListener('click', (e) => {
            if (e.target === modalElement) handleClose();
        });
    });
};

/**
 * Remplacer la fonction confirm() native
 */
window.confirmModern = window.showConfirmModal;

console.log('✅ Système de modals professionnels chargé');
