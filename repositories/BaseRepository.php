<?php
/**
 * Base Repository Class
 * Provides common database operations using PDO
 */

abstract class BaseRepository
{
    protected $connection;
    protected $tableName;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Find record by ID
     */
    public function findById($id, $idField = 'ID')
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} WHERE {$idField} = ? LIMIT 1";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in findById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find all records with optional conditions
     */
    public function findAll($conditions = [], $orderBy = null, $limit = null)
    {
        try {
            $sql = "SELECT * FROM {$this->tableName}";
            $params = [];

            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $field => $value) {
                    $whereClause[] = "{$field} = ?";
                    $params[] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $whereClause);
            }

            if ($orderBy) {
                $sql .= " ORDER BY {$orderBy}";
            }

            if ($limit) {
                $sql .= " LIMIT {$limit}";
            }

            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in findAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Insert new record
     */
    public function insert($data)
    {
        try {
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            
            $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute(array_values($data));
            
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database error in insert: " . $e->getMessage());
            throw new Exception("Error al insertar registro: " . $e->getMessage());
        }
    }

    /**
     * Update record by ID
     */
    public function update($id, $data, $idField = 'ID')
    {
        try {
            $fields = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                $fields[] = "{$field} = ?";
                $params[] = $value;
            }
            $params[] = $id;

            $sql = "UPDATE {$this->tableName} SET " . implode(', ', $fields) . 
                   " WHERE {$idField} = ?";
            
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database error in update: " . $e->getMessage());
            throw new Exception("Error al actualizar registro: " . $e->getMessage());
        }
    }

    /**
     * Delete record by ID
     */
    public function delete($id, $idField = 'ID')
    {
        try {
            $sql = "DELETE FROM {$this->tableName} WHERE {$idField} = ?";
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Database error in delete: " . $e->getMessage());
            throw new Exception("Error al eliminar registro: " . $e->getMessage());
        }
    }

    /**
     * Soft delete (set ACTIVO = 0)
     */
    public function softDelete($id, $idField = 'ID')
    {
        try {
            return $this->update($id, ['ACTIVO' => 0], $idField);
        } catch (Exception $e) {
            error_log("Database error in softDelete: " . $e->getMessage());
            throw new Exception("Error al desactivar registro: " . $e->getMessage());
        }
    }

    /**
     * Count records with optional conditions
     */
    public function count($conditions = [])
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->tableName}";
            $params = [];

            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $field => $value) {
                    $whereClause[] = "{$field} = ?";
                    $params[] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $whereClause);
            }

            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Database error in count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check if record exists
     */
    public function exists($id, $idField = 'ID')
    {
        try {
            $sql = "SELECT 1 FROM {$this->tableName} WHERE {$idField} = ? LIMIT 1";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            error_log("Database error in exists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute custom query
     */
    protected function query($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in query: " . $e->getMessage());
            throw new Exception("Error en consulta de base de datos: " . $e->getMessage());
        }
    }

    /**
     * Execute custom query and return single row
     */
    protected function queryFirst($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in queryFirst: " . $e->getMessage());
            throw new Exception("Error en consulta de base de datos: " . $e->getMessage());
        }
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback()
    {
        return $this->connection->rollback();
    }
}