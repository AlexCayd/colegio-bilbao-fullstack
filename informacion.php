<?php
// info-servidor.php - ELIMINAR DESPUÉS DE REVISAR
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Información del Servidor</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Diagnóstico del Servidor - Colegio Bilbao</h1>
    
    <div class="section">
        <h2>1. PHP Instalado</h2>
        <?php if (function_exists('phpversion')): ?>
            <p class="ok">✓ PHP versión: <?php echo phpversion(); ?></p>
        <?php else: ?>
            <p class="error">✗ PHP no disponible</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>2. Extensiones de Base de Datos</h2>
        <?php
        $extensions = ['mysqli', 'pdo', 'pdo_mysql'];
        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                echo "<p class='ok'>✓ $ext instalado</p>";
            } else {
                echo "<p class='error'>✗ $ext NO instalado</p>";
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>3. Información del Sistema</h2>
        <p><strong>Sistema Operativo:</strong> <?php echo PHP_OS; ?></p>
        <p><strong>Servidor Web:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'No disponible'; ?></p>
        <p><strong>Ruta actual:</strong> <?php echo __DIR__; ?></p>
        <p><strong>Usuario del servidor:</strong> <?php echo get_current_user(); ?></p>
    </div>

    <div class="section">
        <h2>4. Permisos de Escritura</h2>
        <?php
        $testFile = __DIR__ . '/test_write.txt';
        if (file_put_contents($testFile, 'test')) {
            echo "<p class='ok'>✓ Permisos de escritura OK</p>";
            unlink($testFile);
        } else {
            echo "<p class='error'>✗ Sin permisos de escritura</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>5. Variables de Entorno (posibles pistas de DB)</h2>
        <?php
        $envVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DATABASE_URL', 'MYSQL_HOST'];
        $found = false;
        foreach ($envVars as $var) {
            $value = getenv($var);
            if ($value) {
                echo "<p class='ok'>✓ $var encontrado (no se muestra por seguridad)</p>";
                $found = true;
            }
        }
        if (!$found) {
            echo "<p>No se encontraron variables de entorno de BD predefinidas</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>6. Archivos de Configuración Existentes</h2>
        <?php
        $configFiles = ['config.php', 'wp-config.php', '.env', 'database.php', 'settings.php'];
        foreach ($configFiles as $file) {
            if (file_exists(__DIR__ . '/' . $file)) {
                echo "<p class='ok'>✓ Encontrado: $file</p>";
            }
        }
        ?>
    </div>

    <hr>
    <p><strong>⚠️ IMPORTANTE: Elimina este archivo inmediatamente después de revisarlo</strong></p>
</body>
</html>