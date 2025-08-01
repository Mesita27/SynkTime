# Setting Up External Facial Recognition APIs

This guide explains how to configure external facial recognition APIs to enhance the accuracy of the SynkTime biometric system.

## Quick Start - Face++ (Recommended)

Face++ offers a free tier with 1,000 API calls per month, making it perfect for small to medium deployments.

### Step 1: Register for Face++ Account
1. Visit https://www.faceplusplus.com/
2. Create a free account
3. Go to your console and create a new application
4. Note down your API Key and API Secret

### Step 2: Configure SynkTime
1. Copy `config/biometric-config.env.example` to `config/biometric-config.php`
2. Update the configuration:

```php
<?php
return [
    'facial_recognition' => [
        'provider' => 'face_plus_plus',
        
        'face_plus_plus' => [
            'api_key' => 'your_facepp_api_key_here',
            'api_secret' => 'your_facepp_api_secret_here',
            'enabled' => true
        ],
        
        'confidence_threshold' => 0.75,
        'timeout' => 10,
        'fallback_enabled' => true
    ]
];
?>
```

### Step 3: Test Configuration
1. Go to the "Inscripción Biométrica" page
2. Check the "Estado del Sistema Biométrico" section
3. You should see "Configurado y activo" for Facial Recognition

## Azure Face API Setup

Azure offers 30,000 free transactions per month and enterprise-grade reliability.

### Step 1: Create Azure Resource
1. Sign in to Azure Portal
2. Create a new "Face" resource
3. Note your subscription key and endpoint URL

### Step 2: Configure SynkTime
```php
'facial_recognition' => [
    'provider' => 'azure_face',
    
    'azure_face' => [
        'api_key' => 'your_azure_subscription_key',
        'endpoint' => 'https://your-resource.cognitiveservices.azure.com',
        'enabled' => true
    ],
    
    'confidence_threshold' => 0.8
]
```

## AWS Rekognition Setup

Best for organizations already using AWS infrastructure.

### Step 1: AWS Setup
1. Create IAM user with Rekognition permissions
2. Generate access keys
3. Note your preferred region

### Step 2: Configure SynkTime
```php
'facial_recognition' => [
    'provider' => 'aws_rekognition',
    
    'aws_rekognition' => [
        'access_key_id' => 'your_aws_access_key',
        'secret_access_key' => 'your_aws_secret_key',
        'region' => 'us-east-1',
        'enabled' => true
    ]
]
```

## Configuration Options

### Confidence Threshold
Controls how strict the facial verification is:
- `0.6` - More permissive (may allow false positives)
- `0.75` - Balanced (recommended)
- `0.9` - Very strict (may cause false negatives)

### Fallback Settings
- `fallback_enabled: true` - Use local algorithms if external API fails
- `timeout: 10` - API request timeout in seconds

## Troubleshooting

### Common Issues

**1. "No configurado" status**
- Check your API credentials
- Ensure the provider is set correctly
- Verify the API service is enabled

**2. "Configuración incompleta" warning**
- Your provider is selected but credentials may be missing
- Check the API key/secret format

**3. Verification fails consistently**
- Lower the confidence threshold
- Check if facial images are clear and well-lit
- Ensure enrollment images are high quality

### Testing Your Configuration

1. Enroll a test employee with facial pattern
2. Try facial verification during attendance registration
3. Check the verification confidence scores
4. Monitor the biometric logs for success rates

### Performance Optimization

**For High Volume**:
- Consider Azure or AWS for enterprise reliability
- Monitor your API quota usage
- Implement local caching if appropriate

**For Cost Efficiency**:
- Use Face++ free tier for development
- Enable fallback to local algorithms
- Optimize image sizes before API calls

## Security Considerations

1. **API Keys**: Store credentials securely, never commit to version control
2. **Image Data**: Facial images are sent to external services - ensure compliance
3. **Logs**: Monitor API usage for unusual patterns
4. **Fallback**: Always enable local fallback for system reliability

## Support

If you encounter issues:
1. Check the system status in the biometric enrollment page
2. Review the error logs in your web server
3. Test API connectivity using their web consoles
4. Verify your account quotas and billing status

For more information, refer to each provider's documentation:
- Face++: https://console.faceplusplus.com/documents/
- Azure: https://docs.microsoft.com/en-us/azure/cognitive-services/face/
- AWS: https://docs.aws.amazon.com/rekognition/