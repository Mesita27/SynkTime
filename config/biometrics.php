<?php
return [
    // Facial (CompreFace o compatible)
    'face_api' => [
        'base_url'   => getenv('FACE_API_BASE_URL') ?: 'http://localhost:8000',
        'api_key'    => getenv('FACE_API_KEY') ?: '',
        // Umbral de similitud recomendado (ajustar según entorno)
        'min_score'  => (float)(getenv('FACE_MIN_SCORE') ?: 0.85),
        // Etiqueta de colección/servicio si aplica
        'service_tag'=> getenv('FACE_SERVICE_TAG') ?: null,
    ],
    // Huella (microservicio propio sin Docker)
    'fingerprint_api' => [
        'base_url'   => getenv('FINGERPRINT_API_BASE_URL') ?: 'http://localhost:5058',
        // Umbral de similitud recomendado
        'min_score'  => (float)(getenv('FINGERPRINT_MIN_SCORE') ?: 40.0),
    ],
    // Almacenamiento de fotos tomadas en reconocimiento/tradicional
    'storage' => [
        'photos_dir' => __DIR__ . '/../storage/biometrics/photos',
        'placeholders_dir' => __DIR__ . '/../public/images/placeholders',
    ],
];