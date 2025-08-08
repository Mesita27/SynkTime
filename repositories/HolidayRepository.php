<?php
/**
 * HolidayRepository
 * Data access layer for holiday and special day management
 */

class HolidayRepository {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Check if date is a holiday for specific empresa
     */
    public function isHoliday($date, $empresaId) {
        // Check in holidays_cache table (general holidays)
        $sql1 = "SELECT COUNT(*) as count FROM holidays_cache WHERE FECHA = ?";
        $stmt1 = $this->conn->prepare($sql1);
        $stmt1->execute([$date]);
        $general = $stmt1->fetch(PDO::FETCH_ASSOC);
        
        if ($general['count'] > 0) {
            return true;
        }
        
        // Check in dias_civicos table (company-specific holidays)
        $sql2 = "SELECT COUNT(*) as count FROM dias_civicos 
                 WHERE FECHA = ? AND ID_EMPRESA = ? AND ACTIVO = 1";
        $stmt2 = $this->conn->prepare($sql2);
        $stmt2->execute([$date, $empresaId]);
        $company = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        return $company['count'] > 0;
    }
    
    /**
     * Get holiday information for date
     */
    public function getHolidayInfo($date, $empresaId = null) {
        // Check general holidays first
        $sql1 = "SELECT *, 'general' as tipo FROM holidays_cache WHERE FECHA = ?";
        $stmt1 = $this->conn->prepare($sql1);
        $stmt1->execute([$date]);
        $general = $stmt1->fetch(PDO::FETCH_ASSOC);
        
        if ($general) {
            return $general;
        }
        
        // Check company-specific holidays
        if ($empresaId) {
            $sql2 = "SELECT *, 'empresa' as tipo FROM dias_civicos 
                     WHERE FECHA = ? AND ID_EMPRESA = ? AND ACTIVO = 1";
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->execute([$date, $empresaId]);
            return $stmt2->fetch(PDO::FETCH_ASSOC);
        }
        
        return false;
    }
    
    /**
     * Get holidays in date range
     */
    public function getHolidaysInRange($startDate, $endDate, $empresaId = null) {
        $holidays = [];
        
        // Get general holidays
        $sql1 = "SELECT *, 'general' as tipo FROM holidays_cache 
                 WHERE FECHA BETWEEN ? AND ?";
        $stmt1 = $this->conn->prepare($sql1);
        $stmt1->execute([$startDate, $endDate]);
        $holidays = array_merge($holidays, $stmt1->fetchAll(PDO::FETCH_ASSOC));
        
        // Get company-specific holidays
        if ($empresaId) {
            $sql2 = "SELECT *, 'empresa' as tipo FROM dias_civicos 
                     WHERE FECHA BETWEEN ? AND ? AND ID_EMPRESA = ? AND ACTIVO = 1";
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->execute([$startDate, $endDate, $empresaId]);
            $holidays = array_merge($holidays, $stmt2->fetchAll(PDO::FETCH_ASSOC));
        }
        
        // Remove duplicates and sort by date
        $uniqueHolidays = [];
        foreach ($holidays as $holiday) {
            $key = $holiday['FECHA'];
            if (!isset($uniqueHolidays[$key])) {
                $uniqueHolidays[$key] = $holiday;
            }
        }
        
        ksort($uniqueHolidays);
        return array_values($uniqueHolidays);
    }
    
    /**
     * Add company holiday
     */
    public function addCompanyHoliday($empresaId, $fecha, $descripcion) {
        $sql = "INSERT INTO dias_civicos (ID_EMPRESA, FECHA, DESCRIPCION, ACTIVO, CREATED_AT) 
                VALUES (?, ?, ?, 1, NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$empresaId, $fecha, $descripcion]);
    }
    
    /**
     * Remove company holiday
     */
    public function removeCompanyHoliday($empresaId, $fecha) {
        $sql = "UPDATE dias_civicos SET ACTIVO = 0 WHERE ID_EMPRESA = ? AND FECHA = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$empresaId, $fecha]);
    }
    
    /**
     * Get upcoming holidays
     */
    public function getUpcoming($empresaId = null, $days = 30) {
        $endDate = date('Y-m-d', strtotime("+{$days} days"));
        return $this->getHolidaysInRange(date('Y-m-d'), $endDate, $empresaId);
    }
    
    /**
     * Ensure holidays tables exist
     */
    public function ensureTablesExist() {
        // Create holidays_cache table for general holidays
        $sql1 = "CREATE TABLE IF NOT EXISTS holidays_cache (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            FECHA DATE NOT NULL,
            DESCRIPCION VARCHAR(255),
            TIPO VARCHAR(50) DEFAULT 'general',
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_fecha (FECHA)
        )";
        $this->conn->exec($sql1);
        
        // Create dias_civicos table for company-specific holidays
        $sql2 = "CREATE TABLE IF NOT EXISTS dias_civicos (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            ID_EMPRESA INT NOT NULL,
            FECHA DATE NOT NULL,
            DESCRIPCION VARCHAR(255),
            ACTIVO TINYINT(1) DEFAULT 1,
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_empresa (ID_EMPRESA),
            INDEX idx_fecha (FECHA),
            UNIQUE KEY unique_empresa_fecha (ID_EMPRESA, FECHA)
        )";
        $this->conn->exec($sql2);
    }
}
?>