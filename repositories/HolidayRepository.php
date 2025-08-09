<?php
/**
 * Holiday Repository
 * Handles database operations for holidays and civic days
 */

require_once __DIR__ . '/BaseRepository.php';

class HolidayRepository extends BaseRepository
{
    protected $tableName = 'dias_civicos';
    protected $cacheTableName = 'holidays_cache';

    public function __construct($connection)
    {
        parent::__construct($connection);
        $this->ensureTablesExist();
    }

    /**
     * Ensure holiday tables exist
     */
    private function ensureTablesExist()
    {
        try {
            // Create dias_civicos table
            $sql = "CREATE TABLE IF NOT EXISTS dias_civicos (
                ID INT AUTO_INCREMENT PRIMARY KEY,
                ID_EMPRESA INT NOT NULL,
                FECHA DATE NOT NULL,
                NOMBRE VARCHAR(255) NOT NULL,
                DESCRIPCION TEXT,
                ACTIVO TINYINT(1) DEFAULT 1,
                CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_empresa_fecha (ID_EMPRESA, FECHA),
                INDEX idx_fecha (FECHA),
                INDEX idx_activo (ACTIVO)
            )";
            
            $this->connection->exec($sql);

            // Create holidays_cache table for better performance
            $sql = "CREATE TABLE IF NOT EXISTS holidays_cache (
                ID INT AUTO_INCREMENT PRIMARY KEY,
                ID_EMPRESA INT NOT NULL,
                YEAR YEAR NOT NULL,
                HOLIDAYS_JSON TEXT NOT NULL,
                CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_empresa_year (ID_EMPRESA, YEAR),
                INDEX idx_year (YEAR)
            )";
            
            $this->connection->exec($sql);
        } catch (PDOException $e) {
            error_log("Error creating holiday tables: " . $e->getMessage());
        }
    }

    /**
     * Check if a date is a holiday for a company
     */
    public function isHoliday($fecha, $idEmpresa)
    {
        try {
            // First check cache
            $year = date('Y', strtotime($fecha));
            $cachedHolidays = $this->getCachedHolidays($idEmpresa, $year);
            
            if ($cachedHolidays) {
                return in_array($fecha, $cachedHolidays);
            }

            // Direct database check
            $sql = "SELECT COUNT(*) as count FROM {$this->tableName} 
                    WHERE ID_EMPRESA = ? AND FECHA = ? AND ACTIVO = 1";

            $result = $this->queryFirst($sql, [$idEmpresa, $fecha]);
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Error checking if date is holiday: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get holidays for a company and year from cache
     */
    private function getCachedHolidays($idEmpresa, $year)
    {
        try {
            $sql = "SELECT HOLIDAYS_JSON FROM {$this->cacheTableName} 
                    WHERE ID_EMPRESA = ? AND YEAR = ?";

            $result = $this->queryFirst($sql, [$idEmpresa, $year]);
            
            if ($result) {
                return json_decode($result['HOLIDAYS_JSON'], true);
            }

            // Cache miss - build cache
            return $this->buildHolidaysCache($idEmpresa, $year);
        } catch (Exception $e) {
            error_log("Error getting cached holidays: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Build holidays cache for a company and year
     */
    private function buildHolidaysCache($idEmpresa, $year)
    {
        try {
            $sql = "SELECT FECHA FROM {$this->tableName} 
                    WHERE ID_EMPRESA = ? AND YEAR(FECHA) = ? AND ACTIVO = 1
                    ORDER BY FECHA";

            $holidays = $this->query($sql, [$idEmpresa, $year]);
            $holidayDates = array_column($holidays, 'FECHA');

            // Store in cache
            $this->connection->prepare(
                "INSERT INTO {$this->cacheTableName} (ID_EMPRESA, YEAR, HOLIDAYS_JSON) 
                 VALUES (?, ?, ?) 
                 ON DUPLICATE KEY UPDATE HOLIDAYS_JSON = VALUES(HOLIDAYS_JSON), UPDATED_AT = NOW()"
            )->execute([$idEmpresa, $year, json_encode($holidayDates)]);

            return $holidayDates;
        } catch (Exception $e) {
            error_log("Error building holidays cache: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all holidays for a company and date range
     */
    public function getHolidays($idEmpresa, $fechaInicio = null, $fechaFin = null)
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE ID_EMPRESA = ? AND ACTIVO = 1";
            $params = [$idEmpresa];

            if ($fechaInicio && $fechaFin) {
                $sql .= " AND FECHA BETWEEN ? AND ?";
                $params[] = $fechaInicio;
                $params[] = $fechaFin;
            } elseif ($fechaInicio) {
                $sql .= " AND FECHA >= ?";
                $params[] = $fechaInicio;
            }

            $sql .= " ORDER BY FECHA";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting holidays: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get holidays for current year
     */
    public function getCurrentYearHolidays($idEmpresa)
    {
        $currentYear = date('Y');
        $fechaInicio = $currentYear . '-01-01';
        $fechaFin = $currentYear . '-12-31';
        
        return $this->getHolidays($idEmpresa, $fechaInicio, $fechaFin);
    }

    /**
     * Add new holiday
     */
    public function addHoliday($idEmpresa, $fecha, $nombre, $descripcion = null)
    {
        try {
            // Check if holiday already exists
            $existing = $this->queryFirst(
                "SELECT ID FROM {$this->tableName} 
                 WHERE ID_EMPRESA = ? AND FECHA = ?",
                [$idEmpresa, $fecha]
            );

            if ($existing) {
                throw new Exception("Ya existe un día cívico para esta fecha");
            }

            $holidayId = $this->insert([
                'ID_EMPRESA' => $idEmpresa,
                'FECHA' => $fecha,
                'NOMBRE' => $nombre,
                'DESCRIPCION' => $descripcion
            ]);

            // Clear cache for this year
            $this->clearHolidaysCache($idEmpresa, date('Y', strtotime($fecha)));

            return $holidayId;
        } catch (Exception $e) {
            error_log("Error adding holiday: " . $e->getMessage());
            throw new Exception("Error al agregar día cívico: " . $e->getMessage());
        }
    }

    /**
     * Update holiday
     */
    public function updateHoliday($id, $data)
    {
        try {
            // Get original date to clear cache
            $original = $this->findById($id);
            
            $result = $this->update($id, $data);

            if ($original) {
                $this->clearHolidaysCache($original['ID_EMPRESA'], date('Y', strtotime($original['FECHA'])));
            }

            // If date changed, also clear cache for new year
            if (isset($data['FECHA']) && $original && $data['FECHA'] !== $original['FECHA']) {
                $this->clearHolidaysCache($original['ID_EMPRESA'], date('Y', strtotime($data['FECHA'])));
            }

            return $result;
        } catch (Exception $e) {
            error_log("Error updating holiday: " . $e->getMessage());
            throw new Exception("Error al actualizar día cívico: " . $e->getMessage());
        }
    }

    /**
     * Delete holiday
     */
    public function deleteHoliday($id)
    {
        try {
            $holiday = $this->findById($id);
            
            if (!$holiday) {
                throw new Exception("Día cívico no encontrado");
            }

            $result = $this->softDelete($id);

            // Clear cache
            $this->clearHolidaysCache($holiday['ID_EMPRESA'], date('Y', strtotime($holiday['FECHA'])));

            return $result;
        } catch (Exception $e) {
            error_log("Error deleting holiday: " . $e->getMessage());
            throw new Exception("Error al eliminar día cívico: " . $e->getMessage());
        }
    }

    /**
     * Clear holidays cache for a specific year
     */
    private function clearHolidaysCache($idEmpresa, $year)
    {
        try {
            $sql = "DELETE FROM {$this->cacheTableName} 
                    WHERE ID_EMPRESA = ? AND YEAR = ?";

            $stmt = $this->connection->prepare($sql);
            return $stmt->execute([$idEmpresa, $year]);
        } catch (Exception $e) {
            error_log("Error clearing holidays cache: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get upcoming holidays
     */
    public function getUpcomingHolidays($idEmpresa, $limit = 5)
    {
        try {
            $today = date('Y-m-d');
            
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE ID_EMPRESA = ? AND FECHA >= ? AND ACTIVO = 1
                    ORDER BY FECHA ASC
                    LIMIT ?";

            return $this->query($sql, [$idEmpresa, $today, $limit]);
        } catch (Exception $e) {
            error_log("Error getting upcoming holidays: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get holidays statistics
     */
    public function getHolidayStats($idEmpresa, $year = null)
    {
        try {
            $year = $year ?? date('Y');
            
            $sql = "SELECT 
                        COUNT(*) as total_holidays,
                        COUNT(CASE WHEN MONTH(FECHA) BETWEEN 1 AND 3 THEN 1 END) as q1_holidays,
                        COUNT(CASE WHEN MONTH(FECHA) BETWEEN 4 AND 6 THEN 1 END) as q2_holidays,
                        COUNT(CASE WHEN MONTH(FECHA) BETWEEN 7 AND 9 THEN 1 END) as q3_holidays,
                        COUNT(CASE WHEN MONTH(FECHA) BETWEEN 10 AND 12 THEN 1 END) as q4_holidays
                    FROM {$this->tableName} 
                    WHERE ID_EMPRESA = ? AND YEAR(FECHA) = ? AND ACTIVO = 1";

            return $this->queryFirst($sql, [$idEmpresa, $year]);
        } catch (Exception $e) {
            error_log("Error getting holiday stats: " . $e->getMessage());
            return [
                'total_holidays' => 0,
                'q1_holidays' => 0,
                'q2_holidays' => 0,
                'q3_holidays' => 0,
                'q4_holidays' => 0
            ];
        }
    }

    /**
     * Import Colombian national holidays for a year
     */
    public function importColombianHolidays($idEmpresa, $year)
    {
        try {
            // Basic Colombian holidays (some are fixed, others are calculated)
            $fixedHolidays = [
                ['fecha' => "$year-01-01", 'nombre' => 'Año Nuevo'],
                ['fecha' => "$year-05-01", 'nombre' => 'Día del Trabajo'],
                ['fecha' => "$year-07-20", 'nombre' => 'Día de la Independencia'],
                ['fecha' => "$year-08-07", 'nombre' => 'Batalla de Boyacá'],
                ['fecha' => "$year-12-08", 'nombre' => 'Inmaculada Concepción'],
                ['fecha' => "$year-12-25", 'nombre' => 'Navidad']
            ];

            $imported = 0;
            foreach ($fixedHolidays as $holiday) {
                try {
                    $this->addHoliday($idEmpresa, $holiday['fecha'], $holiday['nombre']);
                    $imported++;
                } catch (Exception $e) {
                    // Holiday might already exist, continue
                    continue;
                }
            }

            return $imported;
        } catch (Exception $e) {
            error_log("Error importing Colombian holidays: " . $e->getMessage());
            throw new Exception("Error al importar feriados colombianos: " . $e->getMessage());
        }
    }

    /**
     * Check if date range contains holidays
     */
    public function hasHolidaysInRange($idEmpresa, $fechaInicio, $fechaFin)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->tableName} 
                    WHERE ID_EMPRESA = ? AND FECHA BETWEEN ? AND ? AND ACTIVO = 1";

            $result = $this->queryFirst($sql, [$idEmpresa, $fechaInicio, $fechaFin]);
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Error checking holidays in range: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get holidays that fall on working days for a date range
     */
    public function getWorkingDayHolidays($idEmpresa, $fechaInicio, $fechaFin)
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE ID_EMPRESA = ? 
                    AND FECHA BETWEEN ? AND ? 
                    AND ACTIVO = 1
                    AND DAYOFWEEK(FECHA) BETWEEN 2 AND 6  -- Monday to Friday
                    ORDER BY FECHA";

            return $this->query($sql, [$idEmpresa, $fechaInicio, $fechaFin]);
        } catch (Exception $e) {
            error_log("Error getting working day holidays: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clean up old cache entries
     */
    public function cleanupOldCache($years = 2)
    {
        try {
            $cutoffYear = date('Y') - $years;
            
            $sql = "DELETE FROM {$this->cacheTableName} WHERE YEAR < ?";

            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute([$cutoffYear]);
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Error cleaning up old cache: " . $e->getMessage());
            return 0;
        }
    }
}