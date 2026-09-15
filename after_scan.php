<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Attendance - Done</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">  

    <style>
    body {
    background: linear-gradient(to right, #e2e2e2, #c9d6ff);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    }

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
    }

        /* Sidebar */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: var(--sidebar);
            padding: 30px 20px;
            position: fixed;
            box-shadow: 4px 0 10px rgba(0,0,0,0.05);
        }

        .main-content { margin-left: 280px; padding: 40px; width: calc(100% - 280px); }

        /* Modern Grid Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
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

        .admin-form input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1.5px solid #eee;
            border-radius: 10px;
            outline: none;
            transition: 0.3s;
        }

        .admin-form input:focus { border-color: var(--primary); }

        /* Table Styling */
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { color: #888; font-weight: 500; text-align: left; padding: 10px; }
        tr.row-data { background: #fff; box-shadow: 0 5px 10px rgba(0,0,0,0.02); transition: 0.2s; }
        tr.row-data:hover { transform: scale(1.01); }
        td { padding: 15px; border-radius: 0; }
        td:first-child { border-radius: 10px 0 0 10px; }
        td:last-child { border-radius: 0 10px 10px 0; }

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
    
    <div id="reader" style="width: 100%; border-radius: 15px; overflow: hidden; border: 1px solid #eee; background: #000;"></div>
    <form action="process_checkin.php" method="POST" id="qr-form" class="admin-form" style="margin-top: 20px;">
        <!-- will appear the name who registered scanned the QR code -->

        
    </form>

    <!-- <?php if(isset($_GET['status2']) && $_GET['status2'] == 'not_found'): ?>
        <div class="badge" style="background: rgba(255, 16, 16, 0.1); color: #ff1010; display: block; text-align: center; margin-top: 10px; padding: 10px;">
            Check-in failed! Invalid Student ID.
        </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['status']) && $_GET['status'] == 'already_in'): ?>
        <div class="badge" style="background: rgba(255, 152, 0, 0.1); color: #ff9800; display: block; text-align: center; margin-top: 10px; padding: 10px;">
            Already checked in today!
        </div>
    <?php endif; ?> -->

    <?php if(isset($_GET['voucher'])): ?>
        <div class="voucher-box" style="margin-top:20px; padding:15px; background:rgba(108, 99, 255, 0.05); border-radius:12px; border:2px dashed var(--primary); text-align:center;">
            <p style="font-size: 0.8rem; color: #666; margin-bottom: 5px;">Wi-Fi Access Code:</p>
            <h2 style="color:var(--primary); letter-spacing: 2px;"><?php echo htmlspecialchars($_GET['voucher']); ?></h2>
            <small>Valid for 1 hour</small>
        </div>
    <?php endif; ?>

    <a href="scanner.php" class="btn-modern" style="text-decoration:none;"> Back to Scan
                </a>
</div>

<!-- <script src="https://unpkg.com/html5-qrcode"></script>
<script>
function onScanSuccess(decodedText, decodedResult) {
    // 1. Set the input field value
    document.getElementById('student_id').value = decodedText;
    
    // 2. Stop the scanner
    html5QrcodeScanner.clear();
    
    // 3. Auto-submit the form
    document.getElementById('qr-form').submit();
}

function onScanFailure(error) {
    // We ignore errors while searching for a code
}

// MacBook & Android optimized config
let html5QrcodeScanner = new Html5QrcodeScanner(
    "reader", 
    { 
        fps: 20, 
        qrbox: {width: 250, height: 250},
        aspectRatio: 1.0 
    }
);

html5QrcodeScanner.render(onScanSuccess, onScanFailure);

</script> -->


        
    </div>
</div>

</body>
</html> 
