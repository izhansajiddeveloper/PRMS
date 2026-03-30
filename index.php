<?php
require_once 'config/db.php';

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // Redirect to respective dashboard
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'doctor':
            header("Location: doctor/dashboard.php");
            break;
        case 'receptionist':
            header("Location: receptionist/dashboard.php");
            break;
        default:
            // Stay on landing page
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRMS - Patient Record Management System | Digital Healthcare Solution</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%);
        }

        .hero-gradient {
            background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%);
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .stats-card {
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: scale(1.05);
        }

        .feature-icon {
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: rotateY(180deg);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-outline-primary {
            border: 2px solid #3b82f6;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%);
            color: white;
            transform: translateY(-2px);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        .hero-image {
            background: url('https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80') center/cover;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="bg-gray-50">

    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section id="home" class="hero-gradient text-white py-20 overflow-hidden">
        <div class="container mx-auto px-6">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-12">
                <div class="lg:w-1/2 fade-in-up">
                    <h1 class="text-5xl lg:text-6xl font-bold leading-tight mb-6">
                        Digital Patient Record Management System
                    </h1>
                    <p class="text-xl mb-8 text-white text-opacity-90">
                        Transform your healthcare practice with our comprehensive digital solution.
                        Access patient history, manage prescriptions, and improve care quality.
                    </p>
                    <div class="flex space-x-4">
                        <a href="auth/login.php" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:shadow-xl transition transform hover:scale-105">
                            <i class="fas fa-rocket mr-2"></i>Get Started
                        </a>
                        <a href="#features" class="border-2 border-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition">
                            <i class="fas fa-play mr-2"></i>Learn More
                        </a>
                    </div>

                    <!-- Trust Badges -->
                    <div class="mt-12 flex flex-wrap items-center gap-6">
                        <div class="flex items-center">
                            <i class="fas fa-shield-alt text-2xl mr-2"></i>
                            <span>Secure & HIPAA Compliant</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-cloud-upload-alt text-2xl mr-2"></i>
                            <span>Cloud Based</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-headset text-2xl mr-2"></i>
                            <span>24/7 Support</span>
                        </div>
                    </div>
                </div>
                <div class="lg:w-1/2 mt-10 lg:mt-0 floating">
                    <img src="https://cdn-icons-png.flaticon.com/512/4222/4222736.png" alt="Healthcare" class="w-full max-w-md mx-auto">
                </div>
            </div>
        </div>
    </section>>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Powerful Features</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Everything you need to manage patient records efficiently and effectively
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card bg-gray-50 rounded-xl p-8 card-hover">
                    <div class="feature-icon gradient-bg w-16 h-16 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-history text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Complete Medical History</h3>
                    <p class="text-gray-600">Access complete patient history including past visits, diagnoses, prescriptions, and doctor notes instantly.</p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card bg-gray-50 rounded-xl p-8 card-hover">
                    <div class="feature-icon gradient-bg w-16 h-16 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-prescription-bottle-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Prescription Tracking</h3>
                    <p class="text-gray-600">Track all medications prescribed, dosages, and treatment duration for better patient care.</p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card bg-gray-50 rounded-xl p-8 card-hover">
                    <div class="feature-icon gradient-bg w-16 h-16 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-user-md text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Doctor Notes</h3>
                    <p class="text-gray-600">Secure private notes for doctors to document observations and treatment plans.</p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card bg-gray-50 rounded-xl p-8 card-hover">
                    <div class="feature-icon gradient-bg w-16 h-16 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-calendar-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Appointment Management</h3>
                    <p class="text-gray-600">Easy scheduling and management of patient appointments with automated reminders.</p>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card bg-gray-50 rounded-xl p-8 card-hover">
                    <div class="feature-icon gradient-bg w-16 h-16 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-search text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Advanced Search</h3>
                    <p class="text-gray-600">Quickly find patients by name, ID, or phone number with our powerful search system.</p>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card bg-gray-50 rounded-xl p-8 card-hover">
                    <div class="feature-icon gradient-bg w-16 h-16 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Analytics & Reports</h3>
                    <p class="text-gray-600">Generate detailed reports and analytics for better practice management.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section id="benefits" class="py-20 bg-gradient-to-br from-blue-50 to-green-50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Why Choose PRMS?</h2>
                <p class="text-xl text-gray-600">Experience the future of healthcare management</p>
            </div>

            <div class="grid md:grid-cols-2 gap-12">
                <div class="space-y-6">
                    <div class="flex items-start space-x-4">
                        <div class="gradient-bg w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">No More Paper Records</h3>
                            <p class="text-gray-600">Eliminate paper clutter and access all records digitally from anywhere.</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4">
                        <div class="gradient-bg w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Better Diagnosis</h3>
                            <p class="text-gray-600">Make informed decisions with complete patient history at your fingertips.</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4">
                        <div class="gradient-bg w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Time Saving</h3>
                            <p class="text-gray-600">Reduce administrative work and focus more on patient care.</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4">
                        <div class="gradient-bg w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Cost Effective</h3>
                            <p class="text-gray-600">Reduce operational costs with efficient digital management.</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-center">
                    <img src="https://cdn-icons-png.flaticon.com/512/2917/2917995.png" alt="Benefits" class="w-full max-w-md floating">
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section id="stats" class="py-20 gradient-bg text-white">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-4 gap-8">
                <div class="text-center stats-card">
                    <i class="fas fa-users text-5xl mb-4"></i>
                    <div class="text-4xl font-bold mb-2">500+</div>
                    <div class="text-lg">Active Users</div>
                </div>
                <div class="text-center stats-card">
                    <i class="fas fa-notes-medical text-5xl mb-4"></i>
                    <div class="text-4xl font-bold mb-2">10,000+</div>
                    <div class="text-lg">Patient Records</div>
                </div>
                <div class="text-center stats-card">
                    <i class="fas fa-calendar-check text-5xl mb-4"></i>
                    <div class="text-4xl font-bold mb-2">50,000+</div>
                    <div class="text-lg">Appointments</div>
                </div>
                <div class="text-center stats-card">
                    <i class="fas fa-smile text-5xl mb-4"></i>
                    <div class="text-4xl font-bold mb-2">99%</div>
                    <div class="text-lg">Satisfaction Rate</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-4xl font-bold text-gray-800 mb-4">Ready to Transform Your Practice?</h2>
            <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                Join hundreds of healthcare providers using PRMS to deliver better patient care
            </p>
            <a href="auth/login.php" class="btn-primary text-white px-12 py-4 rounded-lg font-bold text-lg inline-block transition transform hover:scale-105">
                <i class="fas fa-arrow-right mr-2"></i>Get Started Today
            </a>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add scroll effect to navbar
        window.addEventListener('scroll', () => {
            const nav = document.querySelector('nav');
            if (window.scrollY > 100) {
                nav.classList.add('shadow-xl');
            } else {
                nav.classList.remove('shadow-xl');
            }
        });

        // Animate stats on scroll
        const statsSection = document.querySelector('#stats');
        if (statsSection) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const stats = document.querySelectorAll('.stats-card .text-4xl');
                        stats.forEach(stat => {
                            const target = parseInt(stat.innerText);
                            let current = 0;
                            const increment = target / 50;
                            const updateStats = () => {
                                if (current < target) {
                                    current += increment;
                                    stat.innerText = Math.ceil(current) + (stat.innerText.includes('+') ? '+' : '');
                                    requestAnimationFrame(updateStats);
                                } else {
                                    stat.innerText = target + (stat.innerText.includes('+') ? '+' : '%');
                                }
                            };
                            updateStats();
                        });
                        observer.unobserve(entry.target);
                    }
                });
            });
            observer.observe(statsSection);
        }
    </script>
</body>

</html>