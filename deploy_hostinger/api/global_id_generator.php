<?php
/**
 * GLOBAL ID GENERATOR - SPACEX GRADE
 * Bulletproof ID generation for POS system
 * Format: VNT-XXX (VNT-001, VNT-002, etc.)
 */

class GlobalIdGenerator {
    private static $pdo = null;
    
    /**
     * Initialize database connection
     */
    private static function initPDO() {
        if (self::$pdo === null) {
            require_once __DIR__ . '/bd_conexion.php';
            self::$pdo = Conexion::obtenerConexion();
            
            if (!self::$pdo) {
                throw new Exception('Critical: Database connection failed');
            }
            
            // Create sequence table if not exists
            self::createSequenceTable();
        }
        return self::$pdo;
    }
    
    /**
     * Create sequence table for atomic counters
     */
    private static function createSequenceTable() {
        $sql = "CREATE TABLE IF NOT EXISTS id_sequences (
            name VARCHAR(50) PRIMARY KEY,
            current_value INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB";
        
        self::$pdo->exec($sql);
        
        // Initialize sales sequence if not exists
        $stmt = self::$pdo->prepare("INSERT IGNORE INTO id_sequences (name, current_value) VALUES ('ventas', 0)");
        $stmt->execute();
    }
    
    /**
     * Generate next sales ID with atomic increment
     * @return string Formatted ID (VNT-001, VNT-002, etc.)
     */
    public static function generateSalesId() {
        try {
            $pdo = self::initPDO();
            
            // Start transaction for atomicity
            $pdo->beginTransaction();
            
            // Lock row and increment counter atomically
            $stmt = $pdo->prepare("
                UPDATE id_sequences 
                SET current_value = current_value + 1 
                WHERE name = 'ventas'
            ");
            $stmt->execute();
            
            // Get the new value
            $stmt = $pdo->prepare("SELECT current_value FROM id_sequences WHERE name = 'ventas' FOR UPDATE");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                throw new Exception('Failed to generate sequence number');
            }
            
            $sequence = $result['current_value'];
            $pdo->commit();
            
            // Format as VNT-XXX with zero padding
            $formattedId = sprintf('VNT-%03d', $sequence);
            
            // Log for audit trail
            error_log("Generated sales ID: {$formattedId} (sequence: {$sequence})");
            
            return $formattedId;
            
        } catch (Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception('Critical: Failed to generate sales ID - ' . $e->getMessage());
        }
    }
    
    /**
     * Get current sales sequence without incrementing
     * @return int Current sequence number
     */
    public static function getCurrentSalesSequence() {
        try {
            $pdo = self::initPDO();
            $stmt = $pdo->prepare("SELECT current_value FROM id_sequences WHERE name = 'ventas'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? (int)$result['current_value'] : 0;
            
        } catch (Exception $e) {
            throw new Exception('Failed to get current sequence: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate sales ID format
     * @param string $id
     * @return bool
     */
    public static function validateSalesId($id) {
        return preg_match('/^VNT-\d{3}$/', $id) === 1;
    }
    
    /**
     * Extract sequence number from sales ID
     * @param string $id
     * @return int|null
     */
    public static function extractSequence($id) {
        if (self::validateSalesId($id)) {
            return (int)substr($id, 4);
        }
        return null;
    }
    
    /**
     * Reset sequence (ADMIN ONLY - for testing)
     * @param int $value
     */
    public static function resetSequence($value = 0) {
        try {
            $pdo = self::initPDO();
            $stmt = $pdo->prepare("UPDATE id_sequences SET current_value = ? WHERE name = 'ventas'");
            $stmt->execute([$value]);
            
            error_log("ADMIN: Sales sequence reset to {$value}");
            
        } catch (Exception $e) {
            throw new Exception('Failed to reset sequence: ' . $e->getMessage());
        }
    }
}
?>

