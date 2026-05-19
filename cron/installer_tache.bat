@echo off
:: Crée une tâche planifiée Windows pour envoyer le bilan à 20h00 chaque jour
:: Exécuter ce fichier en tant qu'Administrateur

schtasks /create ^
  /tn "StoreSuite_Bilan_Journalier" ^
  /tr "\"C:\xampp\php\php.exe\" \"C:\xampp\htdocs\couture\cron\bilan_journalier.php\"" ^
  /sc daily ^
  /st 20:00 ^
  /ru SYSTEM ^
  /f

if %ERRORLEVEL%==0 (
    echo [OK] Tache planifiee creee : StoreSuite_Bilan_Journalier, tous les jours a 20h00
) else (
    echo [ERREUR] Echec creation tache. Relancer en tant qu'Administrateur.
)
pause
