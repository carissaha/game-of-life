<?php
require_once "auth/client_cert_auth.php";

$has_cert = isset($_SERVER['SSL_CLIENT_CERT']) && !empty($_SERVER['SSL_CLIENT_CERT']);
$cert_details = null;
if ($has_cert) {
    $cert_details = openssl_x509_parse($_SERVER['SSL_CLIENT_CERT']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Authentication - Conway's Game of Life</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        
        .cert-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            padding: 30px;
            width: 500px;
            max-width: 100%;
        }
        
        .cert-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .cert-icon.success {
            color: #2ecc71;
        }
        
        .cert-icon.error {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="cert-container">
        <h2 class="text-center mb-3">Certificate Authentication</h2>
        <p class="text-center text-muted mb-4">Conway's Game of Life Secure Access</p>
        
        <div class="text-center mb-4">
            <?php if ($has_cert): ?>
                <div class="cert-icon success">
                    <i class="fas fa-certificate"></i>
                </div>
                <h4>Certificate Detected</h4>
                <p>Your client certificate has been detected.</p>
            <?php else: ?>
                <div class="cert-icon error">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h4>No Certificate Found</h4>
                <p>No client certificate was detected.</p>
            <?php endif; ?>
        </div>
        
        <?php if ($has_cert && $cert_details): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Certificate Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>Common Name:</td>
                            <td><?php echo htmlspecialchars($cert_details['subject']['CN'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td>Issuer:</td>
                            <td><?php echo htmlspecialchars($cert_details['issuer']['O'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td>Valid Until:</td>
                            <td><?php echo date('Y-m-d', $cert_details['validTo_time_t'] ?? time()); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="text-center">
                <a href="game.php" class="btn btn-success">
                    <i class="fas fa-play-circle mr-2"></i>Access Game
                </a>
            </div>
        <?php else: ?>
            <div class="text-center">
                <p>To continue, you need a valid client certificate.</p>
                <div class="mt-3">
                    <a href="auth/login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt mr-2"></i>Use Password Login
                    </a>
                    <a href="index.php" class="btn btn-secondary ml-2">
                        <i class="fas fa-home mr-2"></i>Return to Home
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>