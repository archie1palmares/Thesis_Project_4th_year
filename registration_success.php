<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7fe; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .success-card { background: white; padding: 40px; border-radius: 25px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); text-align: center; max-width: 400px; width: 90%; }
        .qr-frame { background: #f9f9fb; padding: 20px; border-radius: 20px; border: 2px dashed #6c63ff; margin: 25px 0; }
        .btn-action { display: block; width: 100%; padding: 15px; margin-top: 10px; border-radius: 12px; border: none; font-weight: 600; cursor: pointer; text-decoration: none; }
        .btn-print { background: #6c63ff; color: white; }
        .btn-back { background: transparent; color: #888; }
        
        @media print {
            .no-print { display: none; }
            body { background: white; }
            .success-card { box-shadow: none; border: 1px solid #eee; }
        }
    </style>
</head>
<body>

<div class="success-card">
    <div style="color: #10b981; font-size: 50px; margin-bottom: 10px;">
        <i class="fas fa-check-circle"></i>
    </div>
    <h1 style="margin: 0; color: #333;">Registration Live!</h1>
    <p style="color: #666;">Scan this code at the library entrance to check in.</p>

    <div class="qr-frame" id="printableArea">
        <img src="qrcodes/<?php echo $_GET['sid']; ?>.png" style="width: 220px; height: 220px;">
        <h2 style="margin: 15px 0 5px 0; color: #333;"><?php echo htmlspecialchars($_GET['name']); ?></h2>
        <p style="font-family: monospace; color: #6c63ff; font-weight: 700; font-size: 1.1rem; margin: 0;">ID: <?php echo htmlspecialchars($_GET['sid']); ?></p>
        
        
    </div>

    <button onclick="window.print()" class="btn-action btn-print no-print">
        <i class="fas fa-print"></i> Print Library Pass
    </button>
    <a href="admin.php" class="btn-action btn-back no-print">Return to Dashboard</a>
</div>

</body>
</html>