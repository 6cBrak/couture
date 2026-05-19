        </div>
        
        <footer class="footer footer-transparent d-print-none mt-auto border-top">
            <div class="container-xl">
                <div class="row align-items-center py-3">
                    <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
                        <ul class="list-inline mb-0">
                            <?php if (isset($config['telephone']) && !empty($config['telephone'])): ?>
                            <li class="list-inline-item">
                                <a href="tel:<?php echo e($config['telephone']); ?>" class="link-secondary text-decoration-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2"/>
                                    </svg>
                                    <span class="d-none d-lg-inline ms-1"><?php echo e($config['telephone']); ?></span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (isset($config['email']) && !empty($config['email'])): ?>
                            <li class="list-inline-item">
                                <a href="mailto:<?php echo e($config['email']); ?>" class="link-secondary text-decoration-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <rect x="3" y="5" width="18" height="14" rx="2"/>
                                        <polyline points="3 7 12 13 21 7"/>
                                    </svg>
                                    <span class="d-none d-lg-inline ms-1"><?php echo e($config['email']); ?></span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <div class="col-md-4 text-center mb-3 mb-md-0">
                        <div class="text-muted small">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M14.5 9a3.5 4 0 1 0 0 6"/>
                            </svg>
                            <script>document.write(new Date().getFullYear());</script>
                            <?php $site_web_footer = !empty($config['site_web']) ? $config['site_web'] : '#'; ?>
                            <a href="<?php echo e($site_web_footer); ?>" <?php if($site_web_footer !== '#'): ?>target="_blank"<?php endif; ?> class="link-secondary fw-semibold text-decoration-none"><?php echo isset($nom_boutique) ? e($nom_boutique) : 'Store Suite'; ?></a>
                        </div>
                        <div class="text-muted small mt-1">
                            <a href="aide.php" class="link-secondary text-decoration-none">Aide & À Propos</a> | Tous droits reserves
                        </div>
                    </div>

                    <div class="col-md-4 text-center text-md-end">
                        <div class="text-muted small">
                            Intégré par
                            <a href="<?php echo e($site_web_footer); ?>" <?php if($site_web_footer !== '#'): ?>target="_blank"<?php endif; ?> class="link-primary fw-bold text-decoration-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <polyline points="12 3 20 7.5 20 16.5 12 21 4 16.5 4 7.5 12 3"/>
                                    <line x1="12" y1="12" x2="20" y2="7.5"/>
                                    <line x1="12" y1="12" x2="12" y2="21"/>
                                    <line x1="12" y1="12" x2="4" y2="7.5"/>
                                </svg>
                                Store Suite
                            </a>
                        </div>
                        <div class="text-muted small mt-1">Version 2.0</div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Bootstrap JS Bundle (requis pour dropdowns, modals) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Tabler & Custom JS -->
    <script src="<?php echo BASE_URL; ?>assets/js/tabler.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/loader.js"></script>

    <script>
    /**
     * Wrapper global showModal(type, message, onConfirm?)
     * Mappe l'API simple vers showAlertModal / showConfirmModal de modals.js
     */
    window.showModal = function(type, message, onConfirm) {
        if (type === 'confirm') {
            showConfirmModal({
                title: 'Confirmation',
                message: message,
                type: 'danger',
                icon: 'warning',
                confirmText: 'Confirmer',
                cancelText: 'Annuler',
                onConfirm: onConfirm
            });
        } else {
            const map = {
                success: { title: 'Succès',     type: 'success', icon: 'success' },
                error:   { title: 'Erreur',     type: 'danger',  icon: 'error'   },
                warning: { title: 'Attention',  type: 'warning', icon: 'warning' },
                info:    { title: 'Information',type: 'info',    icon: 'info'    }
            };
            const opt = map[type] || map.info;
            showAlertModal({ title: opt.title, message: message, type: opt.type, icon: opt.icon });
        }
    };
    </script>
</body>
</html>
