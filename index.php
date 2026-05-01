<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "login_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$message = "";
$messageType = "";

// Handle Registration
if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);
    
    if ($stmt->execute()) {
        $message = "Registration successful! Please login.";
        $messageType = "success";
    } else {
        $message = "Error: Email already exists!";
        $messageType = "danger";
    }
    $stmt->close();
}

// Handle Login
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_email'] = $row['email'];
            
            // Set cookie if remember me is checked
            if ($remember) {
                setcookie('user_email', $email, time() + (7 * 24 * 60 * 60));
            }
            
            $message = "Login successful!";
            $messageType = "success";
        } else {
            $message = "Invalid password!";
            $messageType = "danger";
        }
    } else {
        $message = "User not found!";
        $messageType = "danger";
    }
    $stmt->close();
}

// Handle Logout
if (isset($_POST['logout'])) {
    session_destroy();
    setcookie('user_email', '', time() - 3600);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Registration - PHP & MySQL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .main-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            max-width: 450px;
            width: 100%;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .btn-custom {
            background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
        }
        
        .nav-tabs {
            border-bottom: 2px solid #eee;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #666;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: #ff6b6b;
            border-bottom: 3px solid #ff6b6b;
            background: transparent;
        }
        
        .cookie-info {
            background: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="main-card">
        <div class="card-header-custom">
            <h3 class="mb-1">🔐 Login System</h3>
            <p class="mb-0">Registration, Login & Cookies</p>
        </div>
        
        <div class="card-body">
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
            
            <ul class="nav nav-tabs mb-4" id="authTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button">
                        Login
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button">
                        Register
                    </button>
                </li>
            </ul>
            
            <div class="tab-content">
                <!-- Login Form -->
                <div class="tab-pane fade show active" id="login" role="tabpanel">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo $_COOKIE['user_email'] ?? ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Remember Me (Cookie)</label>
                        </div>
                        <button type="submit" name="login" class="btn btn-custom">Login</button>
                    </form>
                </div>
                
                <!-- Register Form -->
                <div class="tab-pane fade" id="register" role="tabpanel">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" name="register" class="btn btn-custom">Register</button>
                    </form>
                </div>
            </div>
            
            <?php else: ?>
            <div class="text-center">
                <h4>Welcome, <?php echo $_SESSION['user_name']; ?>!</h4>
                <p class="text-muted"><?php echo $_SESSION['user_email']; ?></p>
                
                <div class="cookie-info">
                    <strong>Session:</strong> Active<br>
                    <strong>Cookie:</strong> <?php echo isset($_COOKIE['user_email']) ? 'Remembered' : 'Not set'; ?>
                </div>
                
                <form method="POST" action="">
                    <button type="submit" name="logout" class="btn btn-danger mt-3">Logout</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>