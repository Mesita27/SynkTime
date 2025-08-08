-- Nuevas tablas no intrusivas para soportar biometría
-- Ajusta el ENGINE/charset según tu estándar.

CREATE TABLE IF NOT EXISTS biometric_identity (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  face_subject_id VARCHAR(191) DEFAULT NULL,
  fingerprint_id VARCHAR(191) DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_employee (employee_id),
  KEY idx_face_subject (face_subject_id),
  KEY idx_fingerprint (fingerprint_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS biometric_event (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  type ENUM('face','fingerprint','photo') NOT NULL,
  score DOUBLE DEFAULT NULL,
  image_path VARCHAR(255) DEFAULT NULL,
  provider_ref VARCHAR(191) DEFAULT NULL, -- subject_id o fingerprint_id
  attendance_id BIGINT UNSIGNED DEFAULT NULL, -- si quieres ligar a la asistencia
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_employee_created (employee_id, created_at),
  KEY idx_type_created (type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Opcional: añade FKs si conoces los nombres reales
-- Usar empleado(ID_EMPLEADO) según el esquema existente
-- ALTER TABLE biometric_identity ADD CONSTRAINT fk_bio_emp FOREIGN KEY (employee_id) REFERENCES empleado(ID_EMPLEADO);
-- ALTER TABLE biometric_event ADD CONSTRAINT fk_bio_evt_emp FOREIGN KEY (employee_id) REFERENCES empleado(ID_EMPLEADO);