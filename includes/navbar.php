<?php
// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['role']);
$user_role = $is_logged_in ? $_SESSION['role'] : '';
$user_name = $is_logged_in ? $_SESSION['user_name'] : '';
?>
<nav class="bg-white shadow-lg sticky top-0 z-50">
    <div class="container mx-auto px-6 py-4">
        <div class="flex items-center justify-between">
            <!-- Logo -->
            <a href="<?php echo BASE_URL; ?>" class="flex items-center space-x-3">
                <div class="gradient-bg w-10 h-10 rounded-lg flex items-center justify-center">
                    <i class="fas fa-hospital-user text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">PRMS</h1>
                    <p class="text-xs text-gray-500">Patient Record Management</p>
                </div>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-8">
                <?php if (!$is_logged_in): ?>
                    <a href="#home" class="text-gray-700 hover:text-blue-600 transition font-medium">Home</a>
                    <a href="#features" class="text-gray-700 hover:text-blue-600 transition font-medium">Features</a>
                    <a href="#benefits" class="text-gray-700 hover:text-blue-600 transition font-medium">Benefits</a>
                    <a href="#stats" class="text-gray-700 hover:text-blue-600 transition font-medium">Statistics</a>
                    <a href="#contact" class="text-gray-700 hover:text-blue-600 transition font-medium">Contact</a>
                    <a href="<?php echo BASE_URL; ?>auth/login.php" class="btn-primary text-white px-6 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                <?php else: ?>
                    <div class="flex items-center space-x-6">
                        <a href="<?php echo BASE_URL . $user_role . '/dashboard.php'; ?>" class="text-gray-700 hover:text-blue-600 transition font-medium">
                            <i class="fas fa-tachometer-alt mr-1"></i>Dashboard
                        </a>
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 gradient-bg rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold">
                                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                                </span>
                            </div>
                            <span class="text-gray-700"><?php echo htmlspecialchars($user_name); ?></span>
                            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="text-red-600 hover:text-red-700 transition">
                                <i class="fas fa-sign-out-alt"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Button -->
            <button id="mobile-menu-btn" class="md:hidden text-gray-700 focus:outline-none">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden mt-4 pb-4">
            <?php if (!$is_logged_in): ?>
                <a href="#home" class="block py-2 text-gray-700 hover:text-blue-600 transition">Home</a>
                <a href="#features" class="block py-2 text-gray-700 hover:text-blue-600 transition">Features</a>
                <a href="#benefits" class="block py-2 text-gray-700 hover:text-blue-600 transition">Benefits</a>
                <a href="#stats" class="block py-2 text-gray-700 hover:text-blue-600 transition">Statistics</a>
                <a href="#contact" class="block py-2 text-gray-700 hover:text-blue-600 transition">Contact</a>
                <a href="<?php echo BASE_URL; ?>auth/login.php" class="block mt-2 btn-primary text-white px-6 py-2 rounded-lg text-center font-semibold">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </a>
            <?php else: ?>
                <a href="<?php echo BASE_URL . $user_role . '/dashboard.php'; ?>" class="block py-2 text-gray-700 hover:text-blue-600 transition">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <div class="py-2 text-gray-700">
                    <i class="fas fa-user-circle mr-2"></i><?php echo htmlspecialchars($user_name); ?>
                </div>
                <a href="<?php echo BASE_URL; ?>auth/logout.php" class="block py-2 text-red-600 hover:text-red-700 transition">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }
</script>