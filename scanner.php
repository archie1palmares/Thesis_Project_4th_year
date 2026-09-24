<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Attendance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">  

    <style>
        :root {
            --primary: #6c63ff;
            --bg: #f4f7fe;
            --sidebar: #ffffff;
        }

        body { 
            background: linear-gradient(to right, #e2e2e2, #c9d6ff);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; 
            margin: 0;
            font-family: sans-serif;
        }

        /* Modern Form Design */
        .card {  
            background: #fff;
            width: 400px;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }

        .camera-select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1.5px solid #eee;
            border-radius: 10px;
            outline: none;
            background: #fff;
            font-size: 0.9rem;
        }

        .admin-form input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1.5px solid #eee;
            border-radius: 10px;
            outline: none;
            transition: 0.3s;
            box-sizing: border-box;
        }

        .admin-form input:focus { border-color: var(--primary); }

        .badge {
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: bold;
            background: rgba(108, 99, 255, 0.1);
            color: var(--primary);
        }
    </style>
</head>
<body>

<div class="card" id="studentCheckIn">
    <h3 style="margin-bottom: 20px;"><i class="fas fa-qrcode"></i> Scan for Attendance</h3>
    
    <!-- Camera selection dropdown -->
    <select id="camera-select" class="camera-select">
        <option value="">Detecting cameras...</option>
    </select>

    <!-- Scanner viewport -->
    <div id="reader" style="width: 100%; height: 280px; border-radius: 15px; overflow: hidden; border: 1px solid #eee; background: #000;"></div>

    <form action="process_checkin.php" method="POST" id="qr-form" class="admin-form" style="margin-top: 20px;">
        <input type="text" name="student_id" id="student_id" placeholder="Student ID Number" required>
        <button type="submit" class="btn" style="width:100%; background:var(--primary); color:white; border:none; padding:12px; border-radius:10px; cursor:pointer;">Manual Check-In</button>
    </form>

    <?php if(isset($_GET['status2']) &&$_GET['status2'] == 'not_found'): ?>
        <div class="badge" style="background: rgba(255, 16, 16, 0.1); color: #ff1010; display: block; text-align: center; margin-top: 10px; padding: 10px;">
            Check-in failed! Invalid Student ID.
        </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['status']) &&$_GET['status'] == 'already_in'): ?>
        <div class="badge" style="background: rgba(255, 152, 0, 0.1); color: #ff9800; display: block; text-align: center; margin-top: 10px; padding: 10px;">
            Already checked in today!
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['voucher'])): ?>
        <div class="voucher-box" style="margin-top:20px; padding:15px; background:rgba(108, 99, 255, 0.05); border-radius:12px; border:2px dashed var(--primary); text-align:center;">
            <p style="font-size: 0.8rem; color: #666; margin-bottom: 5px;">Wi-Fi Access Code:</p>
            <h2 style="color:var(--primary); letter-spacing: 2px;"><?php echo htmlspecialchars($_GET['voucher']); ?></h2>
            <small>Valid for 1 hour</small>
        </div>
    <?php endif; ?>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
let html5QrCode;

function onScanSuccess(decodedText, decodedResult) {
    document.getElementById('student_id').value = decodedText;
    
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            document.getElementById('qr-form').submit();
        }).catch((err) => {
            console.error("Failed to stop scanner", err);
            document.getElementById('qr-form').submit();
        });
    } else {
        document.getElementById('qr-form').submit();
    }
}

function startScanning(cameraId) {
    const config = { 
        fps: 20, 
        qrbox: { width: 220, height: 220 } 
    };

    // If already running, stop before switching cameras
    if (html5QrCode && html5QrCode.isScanning) {
        html5QrCode.stop().then(() => {
            html5QrCode.start(cameraId, config, onScanSuccess);
        });
    } else {
        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("reader");
        }
        html5QrCode.start(cameraId, config, onScanSuccess);
    }
}

// Get all connected camera devices (built-in & USB webcams)
Html5Qrcode.getCameras().then(devices => {
    const select = document.getElementById('camera-select');
    select.innerHTML = '';

    if (devices && devices.length > 0) {
        devices.forEach((device, index) => {
            const option = document.createElement('option');
            option.value = device.id;
            // Display device label or fallback name
            option.text = device.label || `Camera ${index + 1}`;
            select.appendChild(option);
        });

        // Automatically start scanning with the first camera (or external webcam if available)
        startScanning(devices[0].id);

        // Switch cameras when selection changes
        select.addEventListener('change', (e) => {
            if (e.target.value) {
                startScanning(e.target.value);
            }
        });
    } else {
        select.innerHTML = '<option value="">No cameras detected</option>';
    }
}).catch(err => {
    console.error("Error getting camera devices:", err);
    const select = document.getElementById('camera-select');
    select.innerHTML = '<option value="">Camera access denied or unavailable</option>';
});
</script>

</body>
</html>
