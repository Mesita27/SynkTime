<?php
/**
 * AsistenciaRepository
 * Data access layer for attendance records
 */

class AsistenciaRepository {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Create a new attendance record
     */
    public function create($data) {
        $sql = "INSERT INTO asistencias (
            ID_EMPLEADO, FECHA, HORA, TIPO, TARDANZA, OBSERVACION, 
            FOTO, REGISTRO_MANUAL, ID_HORARIO, VERIFICATION_METHOD, CREATED_AT
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['id_empleado'],
            $data['fecha'],
            $data['hora'],
            $data['tipo'],
            $data['tardanza'] ?? 'N',
            $data['observacion'] ?? null,
            $data['foto'] ?? null,
            $data['registro_manual'] ?? 'N',
            $data['id_horario'] ?? null,
            $data['verification_method'] ?? 'traditional'
        ]);
    }
    
    /**
     * Get attendance records for employee on specific date
     */
    public function getByEmployeeAndDate($employeeId, $date) {
        $sql = "SELECT * FROM asistencias 
                WHERE ID_EMPLEADO = ? AND FECHA = ? 
                ORDER BY HORA ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$employeeId, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get latest attendance record for employee and schedule
     */
    public function getLatestByEmployeeAndSchedule($employeeId, $scheduleId, $date) {
        $sql = "SELECT * FROM asistencias 
                WHERE ID_EMPLEADO = ? AND ID_HORARIO = ? AND FECHA = ?
                ORDER BY HORA DESC LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$employeeId, $scheduleId, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Count attendance entries for employee, schedule and date
     */
    public function countByEmployeeScheduleDate($employeeId, $scheduleId, $date) {
        $sql = "SELECT 
                    COUNT(*) as total_entries,
                    COUNT(CASE WHEN TIPO = 'ENTRADA' THEN 1 END) as entradas,
                    COUNT(CASE WHEN TIPO = 'SALIDA' THEN 1 END) as salidas,
                    MAX(CASE WHEN TIPO = 'ENTRADA' THEN HORA END) as ultima_entrada
                FROM asistencias 
                WHERE ID_EMPLEADO = ? AND ID_HORARIO = ? AND FECHA = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$employeeId, $scheduleId, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update attendance observation
     */
    public function updateObservation($id, $observation) {
        $sql = "UPDATE asistencias SET OBSERVACION = ? WHERE ID_ASISTENCIA = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$observation, $id]);
    }
}
?>