<?php
session_start();
include("../connection.php");

// Debug session contents
var_dump($_SESSION);
echo "<br>User role_as: " . (isset($_SESSION['admin_user']['role_as']) ? $_SESSION['admin_user']['role_as'] : 'not set');
echo "<br>Session ID: " . session_id();

// Check if user is logged in and is admin
if(!isset($_SESSION['admin_user']) || $_SESSION['admin_user']['role_as'] != 1) {
    $response = [
        'valid' => false,
        'message' => 'Session expired or unauthorized. Please login as admin.'
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Get user data from session
$user = $_SESSION['admin_user'];

// Remove debug output before proceeding
ob_clean();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Redemption Code - Freshmart Admin</title>
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .result-container {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-sm {
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div id="dashboardMainContainer">
        <?php include('partials/app-sidebar.php') ?>
        <div class="dasboard_content_container" id="dasboard_content_container">
            <div class="dashboard_topNav">
                <a href="#" id="toggleBtn"><i class="fa fa-navicon"></i></a>
                <a href="logout.php" id="logoutBtn"><i class="fa fa-power-off"></i> Log-out</a>
            </div>
            <div class="dashboard_content">
                <div class="dashboard_content_main">
                    <h1>Check Redemption Code</h1>
                    <div class="form-group">
                        <input type="text" 
                               id="redemption_code" 
                               class="form-control" 
                               style="padding: 10px; width: 300px; margin-right: 10px;"
                               placeholder="Enter redemption code">
                        <button onclick="verifyCode()" 
                                class="btn btn-primary"
                                style="padding: 10px 20px;">
                            Verify Code
                        </button>
                    </div>
                    <div id="result_container"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function verifyCode() {
        const code = document.getElementById('redemption_code').value.trim();
        const resultContainer = document.getElementById('result_container');
        
        if (!code) {
            resultContainer.innerHTML = '<div class="result-container error">Please enter a redemption code</div>';
            return;
        }

        console.log('Sending code:', code); // Debug log

        fetch('verify_redemption_code.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'code=' + encodeURIComponent(code)
        })
        .then(response => {
            console.log('Response status:', response.status); // Debug log
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data); // Debug log
            
            if (data.valid) {
                resultContainer.innerHTML = `
                    <div class="result-container success">
                        <h3>Valid Redemption Code</h3>
                        <p><strong>Code:</strong> ${data.redemption_code}</p>
                        <p><strong>Points Redeemed:</strong> ${data.points_redeemed}</p>
                        <p><strong>Amount:</strong> $${parseFloat(data.amount).toFixed(2)}</p>
                        <p><strong>Created At:</strong> ${data.created_at}</p>
                        <p><strong>Redeemed At:</strong> ${data.redeemed_at || 'Not yet redeemed'}</p>
                        <p><strong>Expires At:</strong> ${data.expires_at}</p>
                        <hr style="margin: 18px 0 10px 0; border: 0; border-top: 1px solid #b2dfb2;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span><strong>Status:</strong> <span id="status-text">${data.status}</span></span>
                            ${data.status === 'pending' ? `
                                <button onclick="updateStatus('${data.redemption_code}', 'redeemed')" 
                                        class="btn btn-success btn-sm" 
                                        style="margin-left: 10px; padding: 2px 12px; min-width: 120px;">
                                    Mark as Redeemed
                                </button>
                            ` : ''}
                        </div>
                    </div>`;
            } else {
                resultContainer.innerHTML = `
                    <div class="result-container error">
                        <h3>Invalid Redemption Code</h3>
                        <p>${data.message}</p>
                        ${data.debug_info ? `<p>Debug Info: ${JSON.stringify(data.debug_info)}</p>` : ''}
                    </div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error); // Debug log
            resultContainer.innerHTML = `
                <div class="result-container error">
                    <h3>Error</h3>
                    <p>An error occurred while verifying the code.</p>
                    <p>Error details: ${error.message}</p>
                </div>`;
        });
    }

    // Add new function to update status
    function updateStatus(code, newStatus) {
        fetch('update_redemption_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `code=${encodeURIComponent(code)}&status=${encodeURIComponent(newStatus)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('status-text').textContent = newStatus;
                // Refresh the verification display
                verifyCode();
            } else {
                alert('Failed to update status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the status');
        });
    }
    </script>
    <script src="js/script.js"></script>
</body>
</html> 