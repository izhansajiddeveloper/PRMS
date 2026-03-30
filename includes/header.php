<?php
// Check if base_url is defined, if not, require db.php
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/db.php';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRMS - Patient Record Management System</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }

        .sidebar-link.active {
            background-color: #3b82f6;
            color: white;
        }

        .sidebar-link:hover:not(.active) {
            background-color: #e5e7eb;
        }

        .transition-smooth {
            transition: all 0.3s ease;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex h-screen">