<?php
// ============================================================
//  config/aws.php — AWS Service Configuration Stubs
//  On AWS EC2: replace these with IAM Role or real credentials
// ============================================================

define('AWS_REGION',        getenv('AWS_DEFAULT_REGION') ?: 'ap-south-1');
define('AWS_BUCKET',        getenv('AWS_BUCKET')         ?: 'shopnest-products');
define('AWS_BUCKET_URL',    getenv('AWS_URL')            ?: '');
define('AWS_SNS_TOPIC_ARN', getenv('AWS_SNS_TOPIC_ARN')  ?: '');

/**
 * Get product image URL.
 * In development: returns local uploads path.
 * In production:  returns S3 URL.
 *
 * @param string $filename
 * @return string
 */
function getProductImageUrl(string $filename): string {
    if (empty($filename)) {
        return APP_URL . '/assets/images/no-image.png';
    }

    // Production: use S3
    if (defined('APP_ENV') && APP_ENV === 'production' && !empty(AWS_BUCKET_URL)) {
        return rtrim(AWS_BUCKET_URL, '/') . '/products/' . $filename;
    }

    // Development: use local uploads folder
    return APP_URL . '/uploads/products/' . $filename;
}

/**
 * Simulate SNS notification (stub).
 * On AWS: use AWS SDK for PHP — Aws\Sns\SnsClient.
 *
 * @param string $message
 * @param string $subject
 * @return bool
 */
function sendSNSNotification(string $message, string $subject = 'ShopNest Notification'): bool {
    // Stub: log notification to file in development
    if (defined('APP_ENV') && APP_ENV !== 'production') {
        $logFile = __DIR__ . '/../logs/sns_stub.log';
        @mkdir(dirname($logFile), 0755, true);
        file_put_contents($logFile,
            date('[Y-m-d H:i:s]') . " SUBJECT: $subject | MESSAGE: $message\n",
            FILE_APPEND
        );
        return true;
    }

    // Production: wire up AWS SDK here
    // $client = new \Aws\Sns\SnsClient([...]);
    // $client->publish(['TopicArn' => AWS_SNS_TOPIC_ARN, 'Message' => $message, 'Subject' => $subject]);
    return false;
}

/**
 * Upload file to S3 (stub for local development).
 *
 * @param string $localPath
 * @param string $s3Key
 * @return string  S3 URL or local URL
 */
function uploadToS3(string $localPath, string $s3Key): string {
    // In production, use AWS SDK:
    // $s3->putObject(['Bucket' => AWS_BUCKET, 'Key' => $s3Key, 'SourceFile' => $localPath, 'ACL' => 'public-read']);
    // return "https://" . AWS_BUCKET . ".s3." . AWS_REGION . ".amazonaws.com/" . $s3Key;
    return APP_URL . '/uploads/' . $s3Key;
}
