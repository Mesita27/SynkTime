<?php
/**
 * EmpleadoRepository
 * Data access layer for employee data
 */

class EmpleadoRepository {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Get employee by ID
     */
    public function getById($employeeId) {
        $sql = "SELECT e.*, est.NOMBRE as ESTABLECIMIENTO_NOMBRE, s.NOMBRE as SEDE_NOMBRE,
                       emp.NOMBRE as EMPRESA_NOMBRE, emp.ID_EMPRESA
                FROM empleados e
                LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE  
                LEFT JOIN empresas emp ON s.ID_EMPRESA = emp.ID_EMPRESA
                WHERE e.ID_EMPLEADO = ? AND e.ACTIVO = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$employeeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search employees by criteria
     */
    public function search($criteria = []) {
        $sql = "SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.DNI, e.CODIGO,
                       est.NOMBRE as ESTABLECIMIENTO_NOMBRE, s.NOMBRE as SEDE_NOMBRE
                FROM empleados e
                LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE";
        
        $where = ["e.ACTIVO = 1"];
        $params = [];
        
        if (!empty($criteria['empresa_id'])) {
            $sql .= " LEFT JOIN empresas emp ON s.ID_EMPRESA = emp.ID_EMPRESA";
            $where[] = "emp.ID_EMPRESA = ?";
            $params[] = $criteria['empresa_id'];
        }
        
        if (!empty($criteria['sede_id'])) {
            $where[] = "s.ID_SEDE = ?";
            $params[] = $criteria['sede_id'];
        }
        
        if (!empty($criteria['establecimiento_id'])) {
            $where[] = "e.ID_ESTABLECIMIENTO = ?";
            $params[] = $criteria['establecimiento_id'];
        }
        
        if (!empty($criteria['codigo'])) {
            $where[] = "e.CODIGO LIKE ?";
            $params[] = "%{$criteria['codigo']}%";
        }
        
        if (!empty($criteria['nombre'])) {
            $where[] = "(e.NOMBRE LIKE ? OR e.APELLIDO LIKE ?)";
            $params[] = "%{$criteria['nombre']}%";
            $params[] = "%{$criteria['nombre']}%";
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY e.NOMBRE, e.APELLIDO";
        
        if (!empty($criteria['limit'])) {
            $sql .= " LIMIT " . intval($criteria['limit']);
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get employees for autocomplete
     */
    public function getForAutocomplete($query, $empresaId = null, $limit = 20) {
        $sql = "SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.CODIGO, e.DNI,
                       CONCAT(e.NOMBRE, ' ', e.APELLIDO) as NOMBRE_COMPLETO
                FROM empleados e";
        
        $where = ["e.ACTIVO = 1"];
        $params = [];
        
        if ($empresaId) {
            $sql .= " LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                      LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                      LEFT JOIN empresas emp ON s.ID_EMPRESA = emp.ID_EMPRESA";
            $where[] = "emp.ID_EMPRESA = ?";
            $params[] = $empresaId;
        }
        
        if ($query) {
            $where[] = "(e.NOMBRE LIKE ? OR e.APELLIDO LIKE ? OR e.CODIGO LIKE ? OR e.DNI LIKE ?)";
            $searchTerm = "%{$query}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " ORDER BY e.NOMBRE, e.APELLIDO LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Validate employee exists and is active
     */
    public function isActive($employeeId) {
        $sql = "SELECT COUNT(*) as count FROM empleados WHERE ID_EMPLEADO = ? AND ACTIVO = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$employeeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    
    /**
     * Get employee count by establishment
     */
    public function getCountByEstablishment($establishmentId = null) {
        $sql = "SELECT COUNT(*) as count FROM empleados WHERE ACTIVO = 1";
        $params = [];
        
        if ($establishmentId) {
            $sql .= " AND ID_ESTABLECIMIENTO = ?";
            $params[] = $establishmentId;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}
?>