<?php
// Vider tous les caches PHP
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache vidé<br>";
}

if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    echo "✅ APC cache vidé<br>";
}

clearstatcache();
echo "✅ Stat cache vidé<br>";

echo "<br><strong>Cache PHP vidé avec succès!</strong><br><br>";
echo "<a href='listes.php?page=mouvements'>→ Accéder à la page Mouvements</a>";
?>
