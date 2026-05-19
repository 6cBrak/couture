<!-- Page Loader avec couleurs dynamiques et icône du système -->
<div id="pageLoader" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>);">
    <div class="loader-content">
        <div class="loader-logo">
            <?php if (!empty($logo_boutique) && file_exists('uploads/logos/' . $logo_boutique)): ?>
                <!-- Logo personnalisé -->
                <img src="<?php echo BASE_URL . 'uploads/logos/' . e($logo_boutique); ?>" alt="Logo" style="max-width: 80px; max-height: 80px; animation: logoFade 2s ease-in-out infinite;">
            <?php else: ?>
                <!-- Icône Shopping Bag par défaut -->
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" class="store-icon">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z" />
                    <path d="M9 11v-5a3 3 0 0 1 6 0v5" />
                </svg>
            <?php endif; ?>
            <div class="store-name"><?php echo strtoupper(e($nom_boutique)); ?></div>
        </div>
        <div class="loader-spinner">
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
        </div>
        <div class="loader-text">Chargement en cours...</div>
        <div class="loader-dots">
            <div class="loader-dot"></div>
            <div class="loader-dot"></div>
            <div class="loader-dot"></div>
        </div>
        <div class="loader-progress">
            <div class="loader-progress-bar"></div>
        </div>
    </div>
</div>
