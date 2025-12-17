<?php
// Script de prueba de conexión a la base de datos Sakila

$mysqli = @new \mysqli('127.0.0.1', 'root', '', 'sakila');

if ($mysqli->connect_errno) {
    echo "❌ ERROR de conexión: " . $mysqli->connect_error . "\n";
    exit(1);
}

echo "✅ Conexión exitosa a MySQL/MariaDB\n\n";

// Probar las consultas
$queries = [
    'Películas' => "SELECT COUNT(*) as total FROM film",
    'Clientes Activos' => "SELECT COUNT(*) as total FROM customer WHERE active = 1",
    'Rentas Activas' => "SELECT COUNT(*) as total FROM rental WHERE return_date IS NULL",
    'Tiendas' => "SELECT COUNT(*) as total FROM store"
];

foreach ($queries as $nombre => $query) {
    $result = $mysqli->query($query);
    if ($result) {
        $data = $result->fetch_assoc();
        echo "📊 {$nombre}: {$data['total']}\n";
    } else {
        echo "❌ Error en consulta de {$nombre}: " . $mysqli->error . "\n";
    }
}

// Verificar que las tablas existen
echo "\n🔍 Verificando tablas:\n";
$tables = ['film', 'customer', 'rental', 'store'];
foreach ($tables as $table) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "  ✓ Tabla '$table' existe\n";
    } else {
        echo "  ✗ Tabla '$table' NO existe\n";
    }
}

$mysqli->close();
echo "\n✅ Prueba completada\n";
