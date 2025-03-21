<?php
/**
 * Classe de Conexão com o Banco de Dados
 * 
 * @package InvestSystem
 * @version 1.0.0
 * @author Bruno Tipster
 * @copyright 2025 InvestSystem
 * @last_modified 2025-03-20 23:54:44 UTC
 */

class Database {
    private static $instance = null;
    private $connection = null;
    
    /**
     * Construtor privado - Padrão Singleton
     */
    private function __construct() {
        try {
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );

            if ($this->connection->connect_error) {
                throw new Exception('Erro de conexão: ' . $this->connection->connect_error);
            }

            $this->connection->set_charset(DB_CHARSET);
            $this->setupDatabase();

        } catch (Exception $e) {
            $this->logError($e);
            die('Erro ao conectar ao banco de dados. Por favor, tente novamente mais tarde.');
        }
    }

    /**
     * Previne clonagem da instância
     */
    private function __clone() {}

    /**
     * Previne deserialização da instância
     */
    private function __wakeup() {}

    /**
     * Obtém instância única da classe
     * 
     * @return Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retorna conexão ativa
     * 
     * @return mysqli
     */
    public function getConnection(): mysqli {
        return $this->connection;
    }

    /**
     * Configuração inicial do banco de dados
     */
    private function setupDatabase(): void {
        // Configurar timezone
        $this->connection->query("SET time_zone = '+00:00'");
        
        // Configurar modo estrito
        $this->connection->query("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");
        
        // Iniciar transação se necessário
        if (!$this->connection->autocommit(TRUE)) {
            throw new Exception('Erro ao configurar autocommit');
        }
    }

    /**
     * Executa query com prepared statement
     * 
     * @param string $query
     * @param array $params
     * @return mysqli_stmt
     */
    public function prepare(string $query, array $params = []): mysqli_stmt {
        try {
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Erro na preparação da query: ' . $this->connection->error);
            }

            if (!empty($params)) {
                $types = '';
                $values = [];
                
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } elseif (is_string($param)) {
                        $types .= 's';
                    } else {
                        $types .= 'b';
                    }
                    $values[] = $param;
                }

                $stmt->bind_param($types, ...$values);
            }

            return $stmt;

        } catch (Exception $e) {
            $this->logError($e);
            throw $e;
        }
    }

    /**
     * Inicia uma transação
     */
    public function beginTransaction(): void {
        $this->connection->autocommit(FALSE);
        $this->connection->begin_transaction();
    }

    /**
     * Confirma uma transação
     */
    public function commit(): void {
        try {
            if (!$this->connection->commit()) {
                throw new Exception('Erro ao confirmar transação');
            }
        } finally {
            $this->connection->autocommit(TRUE);
        }
    }

    /**
     * Reverte uma transação
     */
    public function rollback(): void {
        try {
            if (!$this->connection->rollback()) {
                throw new Exception('Erro ao reverter transação');
            }
        } finally {
            $this->connection->autocommit(TRUE);
        }
    }

    /**
     * Escapa string para uso em query
     * 
     * @param string $string
     * @return string
     */
    public function escape(string $string): string {
        return $this->connection->real_escape_string($string);
    }

    /**
     * Registra erros em arquivo de log
     * 
     * @param Exception $error
     */
    private function logError(Exception $error): void {
        $logMessage = sprintf(
            "[%s] %s in %s:%d\nStack trace:\n%s\n",
            date('Y-m-d H:i:s'),
            $error->getMessage(),
            $error->getFile(),
            $error->getLine(),
            $error->getTraceAsString()
        );

        error_log($logMessage, 3, LOG_PATH . '/database.log');
    }

    /**
     * Fecha a conexão quando o objeto é destruído
     */
    public function __destruct() {
        if ($this->connection !== null) {
            $this->connection->close();
        }
    }
}