<?php
/**
 * HorarioRepository
 * Data access layer for employee schedules
 */

class HorarioRepository {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Get active schedules for employee on specific date
     */
    public function getActiveSchedulesForEmployee($employeeId, $date) {
        $dayOfWeek = date('N', strtotime($date)); // 1 = Monday, 7 = Sunday
        
        $sql = "SELECT h.*, eh.FECHA_DESDE, eh.FECHA_HASTA,
                       CASE $dayOfWeek
                           WHEN 1 THEN h.LUNES
                           WHEN 2 THEN h.MARTES  
                           WHEN 3 THEN h.MIERCOLES
                           WHEN 4 THEN h.JUEVES
                           WHEN 5 THEN h.VIERNES
                           WHEN 6 THEN h.SABADO
                           WHEN 7 THEN h.DOMINGO
                       END as ACTIVO_HOY
                FROM empleado_horarios eh
                JOIN horarios h ON eh.ID_HORARIO = h.ID_HORARIO
                WHERE eh.ID_EMPLEADO = ? 
                AND eh.ACTIVO = 1 
                AND h.ACTIVO = 1
                AND (eh.FECHA_DESDE IS NULL OR eh.FECHA_DESDE <= ?)
                AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= ?)
                HAVING ACTIVO_HOY = 1
                ORDER BY h.HORA_ENTRADA ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$employeeId, $date, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get schedule by ID
     */
    public function getById($scheduleId) {
        $sql = "SELECT * FROM horarios WHERE ID_HORARIO = ? AND ACTIVO = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$scheduleId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get employee schedule assignment for date range
     */
    public function getEmployeeScheduleAssignment($employeeId, $date) {
        $sql = "SELECT eh.*, h.NOMBRE as HORARIO_NOMBRE, h.HORA_ENTRADA, h.HORA_SALIDA, h.TOLERANCIA
                FROM empleado_horarios eh
                JOIN horarios h ON eh.ID_HORARIO = h.ID_HORARIO
                WHERE eh.ID_EMPLEADO = ? 
                AND eh.ACTIVO = 1
                AND h.ACTIVO = 1
                AND (eh.FECHA_DESDE IS NULL OR eh.FECHA_DESDE <= ?)
                AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= ?)
                ORDER BY eh.FECHA_DESDE DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$employeeId, $date, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calculate if time is late based on schedule
     */
    public function isLate($scheduleId, $actualTime, $date) {
        $schedule = $this->getById($scheduleId);
        if (!$schedule) {
            return false;
        }
        
        $entryTime = new DateTime($date . ' ' . $schedule['HORA_ENTRADA']);
        $tolerance = intval($schedule['TOLERANCIA'] ?? 0); // Minutes
        $entryTime->add(new DateInterval("PT{$tolerance}M"));
        
        $actual = new DateTime($date . ' ' . $actualTime);
        
        return $actual > $entryTime;
    }
    
    /**
     * Get late minutes for entry time
     */
    public function getLateMinutes($scheduleId, $actualTime, $date) {
        $schedule = $this->getById($scheduleId);
        if (!$schedule) {
            return 0;
        }
        
        $entryTime = new DateTime($date . ' ' . $schedule['HORA_ENTRADA']);
        $tolerance = intval($schedule['TOLERANCIA'] ?? 0);
        $entryTime->add(new DateInterval("PT{$tolerance}M"));
        
        $actual = new DateTime($date . ' ' . $actualTime);
        
        if ($actual <= $entryTime) {
            return 0;
        }
        
        $diff = $actual->diff($entryTime);
        return ($diff->h * 60) + $diff->i;
    }
    
    /**
     * Get all active schedules
     */
    public function getAllActive() {
        $sql = "SELECT * FROM horarios WHERE ACTIVO = 1 ORDER BY NOMBRE";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if employee has any active schedule
     */
    public function hasActiveSchedule($employeeId) {
        $sql = "SELECT COUNT(*) as count
                FROM empleado_horarios eh
                JOIN horarios h ON eh.ID_HORARIO = h.ID_HORARIO
                WHERE eh.ID_EMPLEADO = ? 
                AND eh.ACTIVO = 1 
                AND h.ACTIVO = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$employeeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
}
?>