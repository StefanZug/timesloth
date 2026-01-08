<?php
// Registriert unsere Autoload-Funktion
spl_autoload_register(function ($className) {
    
    // In diesen Ordnern suchen wir nach Klassen
    $directories = [
        APP_ROOT . '/src/',
        APP_ROOT . '/src/Controllers/',
        APP_ROOT . '/src/Services/',
        APP_ROOT . '/src/Repositories/'
    ];

    // Wir schauen in jedem Ordner nach
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        
        if (file_exists($file)) {
            require_once $file;
            return; // Gefunden! Abbruch.
        }
    }
    
    // Optional: Logging, falls Klasse nicht gefunden wurde (für Debugging)
    // error_log("Autoloader: Konnte Klasse $className nicht finden.");
});