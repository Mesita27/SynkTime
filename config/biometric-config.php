<?php
/**
 * Biometric API Configuration
 * Configure external facial recognition providers
 */

return [
    'facial_recognition' => [
        // Provider options: 'face_plus_plus', 'aws_rekognition', 'azure_face', 'none'
        'provider' => 'none', // Set to 'none' for local processing only
        
        // Face++ Configuration
        'face_plus_plus' => [
            'api_key' => '', // Add your Face++ API key here
            'api_secret' => '', // Add your Face++ API secret here
            'enabled' => false
        ],
        
        // AWS Rekognition Configuration  
        'aws_rekognition' => [
            'access_key_id' => '', // Add your AWS access key ID
            'secret_access_key' => '', // Add your AWS secret access key
            'region' => 'us-east-1',
            'enabled' => false
        ],
        
        // Azure Face API Configuration
        'azure_face' => [
            'api_key' => '', // Add your Azure Face API key
            'endpoint' => '', // Add your Azure Face API endpoint (e.g., https://your-resource.cognitiveservices.azure.com)
            'enabled' => false
        ],
        
        // General settings
        'confidence_threshold' => 0.75, // Minimum confidence for verification (0.0 to 1.0)
        'timeout' => 10, // API request timeout in seconds
        'fallback_enabled' => true, // Use local algorithm if external API fails
        'cache_results' => false // Cache API results (not recommended for security)
    ],
    
    'fingerprint_recognition' => [
        // Placeholder for fingerprint API configurations
        'enabled' => false,
        'provider' => 'none'
    ]
];
?>