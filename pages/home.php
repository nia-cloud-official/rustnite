<?php if (!isset($_SESSION['user_id'])): ?>
    <!-- Epic Landing Page -->
    <div class="min-h-screen -mt-6 -mx-6 relative overflow-hidden">
        <!-- Animated Background -->
        <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-black to-gray-900">
            <div class="absolute inset-0 opacity-20">
                <div class="absolute top-20 left-20 w-72 h-72 bg-orange-500 rounded-full mix-blend-multiply filter blur-xl animate-pulse"></div>
                <div class="absolute top-40 right-20 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl animate-pulse" style="animation-delay: 2s;"></div>
                <div class="absolute bottom-20 left-1/2 w-72 h-72 bg-blue-500 rounded-full mix-blend-multiply filter blur-xl animate-pulse" style="animation-delay: 4s;"></div>
            </div>
        </div>
        
        <!-- Hero Section -->
        <div class="relative z-10 min-h-screen flex items-center justify-center px-6">
            <div class="text-center max-w-6xl mx-auto">
                <!-- Main Title with Epic Animation -->
                <div class="mb-8 relative">
                    <h1 class="text-8xl md:text-9xl font-black mb-6 relative">
                        <span class="bg-gradient-to-r from-orange-400 via-red-500 to-pink-500 bg-clip-text text-transparent animate-pulse">
                            RUST
                        </span>
                        <span class="bg-gradient-to-r from-blue-400 via-purple-500 to-indigo-500 bg-clip-text text-transparent">
                            NITE
                        </span>
                    </h1>
                    
                    <!-- Subtitle with Typewriter Effect -->
                    <div class="text-2xl md:text-3xl text-gray-300 mb-6 font-bold">
                        <span id="typewriter"></span>
                        <span class="animate-pulse">|</span>
                    </div>
                    
                    <p class="text-lg md:text-xl text-gray-400 max-w-3xl mx-auto leading-relaxed">
                        🚀 Drop into the ultimate coding arena where <span class="text-orange-400 font-bold">Rust mastery</span> 
                        meets <span class="text-purple-400 font-bold">battle royale excitement</span>! 
                        Compete, learn, and dominate the leaderboards! 🏆
                    </p>
                </div>
                
                <!-- Epic CTA Buttons -->
                <div class="flex flex-col sm:flex-row justify-center items-center space-y-4 sm:space-y-0 sm:space-x-8 mb-16">
                    <a href="index.php?page=register" class="group relative px-12 py-6 bg-gradient-to-r from-orange-500 to-red-500 rounded-2xl font-black text-xl text-white transform hover:scale-110 transition-all duration-300 shadow-2xl hover:shadow-orange-500/50">
                        <div class="absolute inset-0 bg-gradient-to-r from-orange-600 to-red-600 rounded-2xl blur opacity-75 group-hover:opacity-100 transition duration-300"></div>
                        <div class="relative flex items-center">
                            <i class="fas fa-rocket mr-3 text-2xl"></i>
                            <span>ENTER THE ARENA</span>
                        </div>
                    </a>
                    
                    <a href="index.php?page=login" class="group px-12 py-6 border-3 border-purple-500 rounded-2xl font-bold text-xl text-purple-400 hover:bg-purple-500 hover:text-white transform hover:scale-105 transition-all duration-300">
                        <i class="fas fa-sign-in-alt mr-3"></i>
                        <span>REJOIN BATTLE</span>
                    </a>
                </div>
                
                <!-- Live Stats Counter -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-16">
                    <div class="text-center group">
                        <div class="text-4xl md:text-5xl font-black text-orange-400 mb-2 group-hover:scale-110 transition-transform" id="warriors-count">0</div>
                        <div class="text-gray-400 font-medium">🔥 WARRIORS</div>
                    </div>
                    <div class="text-center group">
                        <div class="text-4xl md:text-5xl font-black text-blue-400 mb-2 group-hover:scale-110 transition-transform" id="challenges-count">0</div>
                        <div class="text-gray-400 font-medium">⚔️ CHALLENGES</div>
                    </div>
                    <div class="text-center group">
                        <div class="text-4xl md:text-5xl font-black text-green-400 mb-2 group-hover:scale-110 transition-transform" id="projects-count">0</div>
                        <div class="text-gray-400 font-medium">🏗️ PROJECTS</div>
                    </div>
                    <div class="text-center group">
                        <div class="text-4xl md:text-5xl font-black text-purple-400 mb-2 group-hover:scale-110 transition-transform">FREE</div>
                        <div class="text-gray-400 font-medium">💎 FOREVER</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Features Section -->
        <div class="relative z-10 py-20 px-6">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-5xl font-black text-center mb-4 bg-gradient-to-r from-orange-400 to-purple-500 bg-clip-text text-transparent">
                    BATTLE-TESTED FEATURES
                </h2>
                <p class="text-xl text-gray-400 text-center mb-16 max-w-3xl mx-auto">
                    Every feature designed to make you a Rust legend. No fluff, just pure coding power! 💪
                </p>
                
                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Interactive Coding -->
                    <div class="group relative p-8 bg-gradient-to-br from-gray-800/50 to-gray-900/50 rounded-3xl border border-gray-700 hover:border-orange-500/50 transition-all duration-300 hover:transform hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-br from-orange-500/10 to-transparent rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="relative">
                            <div class="w-20 h-20 bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:rotate-12 transition-transform">
                                <i class="fas fa-code text-3xl text-white"></i>
                            </div>
                            <h3 class="text-2xl font-bold mb-4 text-center">⚡ LIVE CODING</h3>
                            <p class="text-gray-400 text-center leading-relaxed">
                                Monaco editor with real-time compilation, instant feedback, and syntax highlighting. 
                                Code like a pro from day one! 🎯
                            </p>
                        </div>
                    </div>
                    
                    <!-- Gamification -->
                    <div class="group relative p-8 bg-gradient-to-br from-gray-800/50 to-gray-900/50 rounded-3xl border border-gray-700 hover:border-purple-500/50 transition-all duration-300 hover:transform hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 to-transparent rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="relative">
                            <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:rotate-12 transition-transform">
                                <i class="fas fa-trophy text-3xl text-white"></i>
                            </div>
                            <h3 class="text-2xl font-bold mb-4 text-center">🏆 EPIC REWARDS</h3>
                            <p class="text-gray-400 text-center leading-relaxed">
                                XP system, achievements, leaderboards, and badges. 
                                Turn learning into an addictive game! 🎮
                            </p>
                        </div>
                    </div>
                    
                    <!-- Real Projects -->
                    <div class="group relative p-8 bg-gradient-to-br from-gray-800/50 to-gray-900/50 rounded-3xl border border-gray-700 hover:border-green-500/50 transition-all duration-300 hover:transform hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-br from-green-500/10 to-transparent rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="relative">
                            <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:rotate-12 transition-transform">
                                <i class="fas fa-rocket text-3xl text-white"></i>
                            </div>
                            <h3 class="text-2xl font-bold mb-4 text-center">🚀 REAL BUILDS</h3>
                            <p class="text-gray-400 text-center leading-relaxed">
                                CLI tools, web servers, chat apps, and more. 
                                Build portfolio-worthy projects that matter! 💼
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Call to Action -->
        <div class="relative z-10 py-20 px-6">
            <div class="max-w-4xl mx-auto text-center">
                <div class="p-12 bg-gradient-to-br from-gray-800/80 to-gray-900/80 rounded-3xl border border-gray-700 backdrop-blur-sm">
                    <h2 class="text-4xl md:text-5xl font-black mb-6">
                        <span class="bg-gradient-to-r from-orange-400 to-purple-500 bg-clip-text text-transparent">
                            READY TO DOMINATE?
                        </span>
                    </h2>
                    <p class="text-xl text-gray-300 mb-8 leading-relaxed">
                        Join thousands of developers mastering Rust through epic challenges. 
                        <br class="hidden md:block">
                        <span class="text-orange-400 font-bold">Free forever.</span> 
                        <span class="text-purple-400 font-bold">No credit card.</span> 
                        <span class="text-green-400 font-bold">Pure learning.</span>
                    </p>
                    
                    <div class="flex flex-col sm:flex-row justify-center items-center space-y-4 sm:space-y-0 sm:space-x-6">
                        <a href="index.php?page=register" class="group relative px-10 py-4 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl font-bold text-lg text-white transform hover:scale-105 transition-all duration-300 shadow-xl">
                            <i class="fas fa-play mr-3"></i>
                            START LEARNING NOW
                        </a>
                        <a href="#features" class="px-10 py-4 border-2 border-gray-600 rounded-xl font-bold text-lg text-gray-300 hover:border-orange-500 hover:text-orange-400 transition-all duration-300">
                            <i class="fas fa-info-circle mr-3"></i>
                            LEARN MORE
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        
        .border-3 {
            border-width: 3px;
        }
    </style>

    <script>
        // Typewriter effect
        const phrases = [
            "Battle-Royale Rust Learning 🔥",
            "Master Systems Programming 💪", 
            "Build Epic Projects 🚀",
            "Compete & Dominate 🏆"
        ];
        
        let phraseIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        
        function typeWriter() {
            const currentPhrase = phrases[phraseIndex];
            const typewriterElement = document.getElementById('typewriter');
            
            if (isDeleting) {
                typewriterElement.textContent = currentPhrase.substring(0, charIndex - 1);
                charIndex--;
            } else {
                typewriterElement.textContent = currentPhrase.substring(0, charIndex + 1);
                charIndex++;
            }
            
            let typeSpeed = isDeleting ? 50 : 100;
            
            if (!isDeleting && charIndex === currentPhrase.length) {
                typeSpeed = 2000;
                isDeleting = true;
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                phraseIndex = (phraseIndex + 1) % phrases.length;
                typeSpeed = 500;
            }
            
            setTimeout(typeWriter, typeSpeed);
        }
        
        // Animated counters
        function animateCounter(elementId, target, duration = 2000) {
            const element = document.getElementById(elementId);
            const start = 0;
            const increment = target / (duration / 16);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current);
                }
            }, 16);
        }
        
        // Initialize animations
        document.addEventListener('DOMContentLoaded', function() {
            typeWriter();
            
            // Animate counters with delay
            setTimeout(() => animateCounter('warriors-count', 1247), 500);
            setTimeout(() => animateCounter('challenges-count', 20), 800);
            setTimeout(() => animateCounter('projects-count', 6), 1100);
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
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
    </script>
<?php else: 
    // Redirect logged-in users to dashboard
    header('Location: index.php?page=dashboard');
    exit;
endif; ?>