<?php
require_once '../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: ../admin/dashboard.php");
            break;
        case 'doctor':
            header("Location: ../doctor/dashboard.php");
            break;
        case 'receptionist':
            header("Location: ../receptionist/dashboard.php");
            break;
        case 'lab_assistant':
            header("Location: ../lab_assistant/dashboard.php");
            break;
        default:
            header("Location: ../index.php");
    }
    exit();
}

$error = '';
$message = '';

// Check for logout or session expired messages
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $message = '<div class="mb-4 p-3 bg-green-50 border-l-4 border-green-500 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        <p class="text-green-700 text-xs">You have been successfully logged out.</p>
                    </div>
                </div>';
} elseif (isset($_GET['error']) && $_GET['error'] == 'session_expired') {
    $message = '<div class="mb-4 p-3 bg-yellow-50 border-l-4 border-yellow-500 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-clock text-yellow-500 mr-2"></i>
                        <p class="text-yellow-700 text-xs">Your session has expired. Please login again.</p>
                    </div>
                </div>';
} elseif (isset($_GET['error']) && $_GET['error'] == 'unauthorized') {
    $message = '<div class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                        <p class="text-red-700 text-xs">Unauthorized access. Please login with proper credentials.</p>
                    </div>
                </div>';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; // Plain text password

    // Query to check user with role information
    $query = "SELECT u.*, r.name as role_name 
              FROM users u 
              JOIN roles r ON u.role_id = r.id 
              WHERE u.email = ? AND u.status = 'active'";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        // Verify plain text password
        if ($password == $user['password']) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role_name'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['last_activity'] = time();

            // Redirect based on role
            switch ($user['role_name']) {
                case 'admin':
                    header("Location: ../admin/dashboard.php");
                    break;
                case 'doctor':
                    header("Location: ../doctor/dashboard.php");
                    break;
                case 'receptionist':
                    header("Location: ../receptionist/dashboard.php");
                    break;
                case 'lab_assistant':
                    header("Location: ../lab_assistant/dashboard.php");
                    break;
                default:
                    header("Location: ../index.php");
            }
            exit();
        } else {
            $error = "Invalid email or password";
        }
    } else {
        $error = "Invalid email or password or account inactive";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PRMS | Patient Record Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%);
        }

        .login-card {
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .input-group {
            transition: all 0.2s ease;
        }

        .input-group:focus-within {
            transform: translateX(3px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            animation: float 20s infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-15px) rotate(180deg);
            }
        }
    </style>
</head>

<body class="gradient-bg min-h-screen flex items-center justify-center relative overflow-hidden">
    <!-- Floating Background Shapes -->
    <div class="floating-shapes">
        <div class="shape w-48 h-48 top-20 left-10 opacity-20" style="animation-duration: 15s;"></div>
        <div class="shape w-64 h-64 bottom-20 right-10 opacity-20" style="animation-duration: 25s;"></div>
        <div class="shape w-32 h-32 top-1/2 left-1/2 opacity-10" style="animation-duration: 20s;"></div>
        <div class="shape w-56 h-56 bottom-40 left-20 opacity-15" style="animation-duration: 18s;"></div>
    </div>

    <!-- Main Container -->
    <div class="relative z-10 w-full max-w-sm px-4">
        <!-- Logo & Brand -->
        <div class="text-center mb-6 login-card">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-white rounded-xl shadow-lg mb-3">
                <i class="fas fa-hospital-user text-3xl text-blue-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-1">PRMS</h1>
            <p class="text-white text-opacity-85 text-xs">Patient Record Management System</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-xl shadow-xl overflow-hidden login-card">
            <div class="p-6">
                <!-- Header -->
                <div class="text-center mb-5">
                    <h2 class="text-xl font-bold text-gray-800">Welcome Back</h2>
                    <p class="text-gray-500 text-xs mt-1">Sign in to access your dashboard</p>
                </div>

                <!-- Success/Info Message -->
                <?php if ($message): ?>
                    <?php echo $message; ?>
                <?php endif; ?>

                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="mb-4 p-2 bg-red-50 border-l-4 border-red-500 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-2 text-xs"></i>
                            <p class="text-red-700 text-xs"><?php echo $error; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="" class="space-y-4">
                    <!-- Email Field -->
                    <div class="input-group">
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            <i class="fas fa-envelope mr-1 text-blue-500 text-xs"></i>
                            Email Address
                        </label>
                        <div class="relative">
                            <input type="email" name="email" required
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all"
                                placeholder="Enter your email">
                            <i class="fas fa-envelope absolute right-3 top-2.5 text-gray-400 text-xs"></i>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="input-group">
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            <i class="fas fa-lock mr-1 text-blue-500 text-xs"></i>
                            Password
                        </label>
                        <div class="relative">
                            <input type="password" name="password" required
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all"
                                placeholder="Enter your password">
                            <i class="fas fa-eye-slash absolute right-3 top-2.5 text-gray-400 cursor-pointer text-xs" onclick="togglePassword(this)"></i>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="remember" class="w-3.5 h-3.5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                            <span class="ml-2 text-xs text-gray-600">Remember me</span>
                        </label>
                    </div>

                    <!-- Login Button -->
                    <button type="submit"
                        class="btn-primary w-full py-2 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-300 text-sm">
                        <i class="fas fa-sign-in-alt mr-2 text-xs"></i>
                        Sign In
                    </button>
                </form>

            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-5">
            <p class="text-white text-opacity-80 text-xs">
                <i class="fas fa-shield-alt mr-1 text-xs"></i>
                Secure Login | Protected Healthcare System
            </p>
            <p class="text-white text-opacity-60 text-xs mt-1">
                © <?php echo date('Y'); ?> PRMS - Patient Record Management System
            </p>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(element) {
            const input = element.parentElement.querySelector('input');
            if (input.type === 'password') {
                input.type = 'text';
                element.classList.remove('fa-eye-slash');
                element.classList.add('fa-eye');
            } else {
                input.type = 'password';
                element.classList.remove('fa-eye');
                element.classList.add('fa-eye-slash');
            }
        }

        // Auto-fill credentials function
        function fillCredentials(email, password) {
            const emailField = document.querySelector('input[name="email"]');
            const passwordField = document.querySelector('input[name="password"]');

            emailField.value = email;
            passwordField.value = password;

            // Highlight effect
            emailField.style.borderColor = '#10b981';
            passwordField.style.borderColor = '#10b981';
            emailField.style.backgroundColor = '#f0fdf4';
            passwordField.style.backgroundColor = '#f0fdf4';

            setTimeout(() => {
                emailField.style.borderColor = '#d1d5db';
                passwordField.style.borderColor = '#d1d5db';
                emailField.style.backgroundColor = '';
                passwordField.style.backgroundColor = '';
            }, 1500);
        }

        // Add floating animation to shapes
        const shapes = document.querySelectorAll('.shape');
        shapes.forEach((shape, index) => {
            const randomX = Math.random() * 100;
            const randomY = Math.random() * 100;
            shape.style.left = randomX + '%';
            shape.style.top = randomY + '%';
            shape.style.animationDelay = (index * 2) + 's';
        });

        // Add loading effect on form submit
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const button = this.querySelector('button[type="submit"]');
                button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1 text-xs"></i> Signing In...';
                button.disabled = true;
            });
        }
    </script>
</body>

</html>