<?php
date_default_timezone_set('America/Mexico_City');

/**
 * Configuración de la base de datos según el entorno
 */
class Database
{
    // Configuraciones para diferentes entornos
    private static $configs = array(
        'local' => array(
            'host' => 'localhost',
            'dbname' => 'carta_digital',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4'
        ),
        'production' => array(
            'host' => 'localhost',
            'dbname' => 'u216934227_carta_digital',
            'username' => 'u216934227_root',
            'password' => 'GamerDubstep13.',
            'charset' => 'utf8mb4'
        )
    );

    private $connection = null;

    /**
     * Detecta automáticamente el entorno basado en múltiples métodos
     * @return string
     */
    private function detectEnvironment()
    {
        $isLocal = false;

        // Método 1: Verificar si es CLI
        if (function_exists('php_sapi_name') && php_sapi_name() === 'cli') {
            $isLocal = true;
        }

        // Método 2: Verificar SERVER_NAME
        if (isset($_SERVER['SERVER_NAME'])) {
            $serverName = $_SERVER['SERVER_NAME'];
            if ($serverName == 'localhost' || 
                $serverName == '127.0.0.1' || 
                strpos($serverName, '.local') !== false || 
                strpos($serverName, 'dev') !== false ||
                strpos($serverName, 'test') !== false) {
                $isLocal = true;
            }
        }

        // Método 3: Verificar hostname
        if (function_exists('gethostname')) {
            $hostname = gethostname();
            if (strpos($hostname, 'localhost') !== false || 
                strpos($hostname, '127.0.0.1') !== false ||
                strpos($hostname, 'DESKTOP-') !== false) { // Para Windows
                $isLocal = true;
            }
        }

        // Método 4: Verificar variable de entorno personalizada
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'local') {
            $isLocal = true;
        }

        // Método 5: Verificar IP del servidor
        if (isset($_SERVER['SERVER_ADDR']) && 
            ($_SERVER['SERVER_ADDR'] === '127.0.0.1' || $_SERVER['SERVER_ADDR'] === '::1')) {
            $isLocal = true;
        }

        // Método 6: Verificar si existe archivo .env local (opcional)
        if (file_exists(__DIR__ . '/.env.local')) {
            $isLocal = true;
        }

        return $isLocal ? 'local' : 'production';
    }

    /**
     * Obtiene una conexión PDO a la base de datos
     * @return PDO
     * @throws Exception
     */
    public function getConnection()
    {
        if ($this->connection === null) {
            $env = $this->detectEnvironment();
            $config = self::$configs[$env];
            
            try {
                $dsn = "mysql:host=" . $config['host'] . ";dbname=" . $config['dbname'] . ";charset=" . $config['charset'];
                
                $this->connection = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $config['charset']
                    )
                );
                
                // Log del entorno detectado (solo para debug)
                error_log("Conectado al entorno: " . $env . " - Base de datos: " . $config['dbname']);
                
            } catch (PDOException $e) {
                error_log("Error de conexión a BD (" . $env . "): " . $e->getMessage());
                throw new Exception("No se pudo conectar a la base de datos");
            }
        }
        
        return $this->connection;
    }

    /**
     * Obtiene el entorno actual (útil para debugging)
     * @return string
     */
    public function getCurrentEnvironment()
    {
        return $this->detectEnvironment();
    }

    /**
     * Cierra la conexión
     */
    public function closeConnection()
    {
        $this->connection = null;
    }
}
?>