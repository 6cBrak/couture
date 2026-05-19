<!DOCTYPE html>
<html>
<head>
    <title>Test Info Produit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Test de Sélection Produit</h2>
        
        <div class="mb-3">
            <label class="form-label">Sélectionner un produit</label>
            <select class="form-select" id="test_produit">
                <option value="">Choisir...</option>
                <option value="1">Produit Test 1</option>
                <option value="2">Produit Test 2</option>
                <option value="3">Produit Test 3</option>
            </select>
        </div>
        
        <div class="alert alert-info" id="result" style="display: none;">
            <div id="result_content"></div>
        </div>
        
        <div class="mt-3">
            <h4>Console Log:</h4>
            <pre id="console_log" class="bg-dark text-white p-3" style="height: 200px; overflow-y: auto;"></pre>
        </div>
    </div>

    <script>
        const consoleLogDiv = document.getElementById('console_log');
        
        function addLog(message) {
            const timestamp = new Date().toLocaleTimeString();
            consoleLogDiv.textContent += `[${timestamp}] ${message}\n`;
            consoleLogDiv.scrollTop = consoleLogDiv.scrollHeight;
        }
        
        const selectProduit = document.getElementById('test_produit');
        const resultDiv = document.getElementById('result');
        const resultContent = document.getElementById('result_content');
        
        addLog('Script chargé');
        addLog('Sélecteur trouvé: ' + (selectProduit ? 'OUI' : 'NON'));
        
        if (selectProduit) {
            // Test avec change
            selectProduit.addEventListener('change', function() {
                const value = this.value;
                const text = this.options[this.selectedIndex]?.text;
                
                addLog(`EVENT CHANGE - Valeur: ${value}, Texte: ${text}`);
                
                if (value) {
                    resultContent.innerHTML = `<strong>Produit ${value} sélectionné:</strong> ${text}`;
                    resultDiv.style.display = 'block';
                } else {
                    resultDiv.style.display = 'none';
                }
            });
            
            // Test avec input aussi
            selectProduit.addEventListener('input', function() {
                addLog(`EVENT INPUT - Valeur: ${this.value}`);
            });
            
            addLog('Event listeners ajoutés');
        }
    </script>
</body>
</html>
