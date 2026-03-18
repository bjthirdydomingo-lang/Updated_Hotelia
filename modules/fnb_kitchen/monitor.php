<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Order Board | Hotelia</title>
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Arima:wght@700&family=Mulish:wght@800&display=swap');

        body { 
            background: #f8fafc; 
            font-family: 'Mulish', sans-serif; 
            overflow: hidden; 
            color: #1e293b;
        }

        /* Teal Header */
        .teal-header {
            background: #008080;
            color: white;
            box-shadow: 0 4px 20px rgba(0, 128, 128, 0.3);
        }

        /* Number Cards */
        .number-card {
            font-size: 4.5rem;
            font-weight: 800;
            color: #008080;
            background: white;
            padding: 20px 10px;
            border-radius: 1.5rem;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            user-select: none;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            height: 160px;
        }

        .number-card:hover {
            background-color: #f0fdfa;
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0, 128, 128, 0.25);
            border-color: #008080;
        }

        /* Preparing Numbers */
        .prep-number {
            font-size: 2.5rem;
            color: #ef4444;
            font-weight: 700;
            padding: 15px;
            border: 2px solid #fee2e2;
            background: #fef2f2;
            border-radius: 1rem;
            text-align: center;
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.1);
        }

        /* Glass Panel - Now White */
        .glass-panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.01);
        }

        .serving-header {
            background: #008080;
            border-radius: 1rem;
            padding: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 128, 128, 0.3);
        }

        /* Preparing Header */
        .preparing-header {
            background: #ef4444;
            border-radius: 1rem;
            padding: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        /* Grid Layouts */
        #serving-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 10px;
            height: calc(100% - 80px);
            overflow-y: auto;
        }

        #preparing-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
        }

        /* Pulse Animation */
        .pulse-glow {
            animation: pulse-teal 2s infinite;
        }

        @keyframes pulse-teal {
            0% { box-shadow: 0 0 0 0 rgba(0, 128, 128, 0.5); }
            70% { box-shadow: 0 0 0 10px rgba(0, 128, 128, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 128, 128, 0); }
        }

        /* Red Pulse for Preparing */
        .pulse-red {
            animation: pulse-red 2s infinite;
        }

        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        /* Steam Animation */
        .steam-container {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
            filter: blur(2px);
        }

        .steam-line {
            width: 6px;
            height: 40px;
            background: rgba(0, 128, 128, 0.3);
            border-radius: 50%;
            animation: rise-and-fade 2s infinite ease-in-out;
        }

        .steam-line:nth-child(2) { animation-delay: 0.4s; height: 50px; }
        .steam-line:nth-child(3) { animation-delay: 0.8s; }

        @keyframes rise-and-fade {
            0% { transform: translateY(0) scaleX(1); opacity: 0; }
            50% { opacity: 0.5; transform: translateY(-20px) scaleX(1.5); }
            100% { transform: translateY(-50px) scaleX(2); opacity: 0; }
        }

        /* Cooking Waiter Animation Container */
        .cooking-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 10px;
            animation: bounce 2s infinite ease-in-out;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Cooking Waiter GIF */
        .cooking-waiter {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 10px 15px rgba(239, 68, 68, 0.3));
            animation: spin-slow 10s infinite linear;
        }

        @keyframes spin-slow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Floating food particles around waiter */
        .floating-food {
            position: absolute;
            font-size: 20px;
            animation: float-around 3s infinite ease-in-out;
        }

        .food-1 { top: -10px; right: -10px; animation-delay: 0s; }
        .food-2 { bottom: -10px; left: -10px; animation-delay: 0.5s; }
        .food-3 { top: 20px; left: -20px; animation-delay: 1s; }
        .food-4 { bottom: 20px; right: -20px; animation-delay: 1.5s; }

        @keyframes float-around {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(5px, -5px) rotate(5deg); }
            50% { transform: translate(0, -10px) rotate(0deg); }
            75% { transform: translate(-5px, -5px) rotate(-5deg); }
        }

        /* Clock */
        .clock {
            color: #008080;
            font-weight: 800;
            background: white;
            padding: 0.5rem 1.5rem;
            border-radius: 3rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        /* Start Overlay */
        #start-overlay {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            color: #008080;
        }

        /* Logo */
        .logo-container {
            background: white;
            border-radius: 1rem;
            padding: 0.5rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        /* Food Particles */
        #particleCanvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            opacity: 0.6;
            z-index: -1;
        }

        /* Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #008080;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #006666;
        }
    </style>
</head>
<body class="h-screen flex flex-col p-6 gap-6">
    <!-- Start Overlay -->
    <div id="start-overlay" class="fixed inset-0 z-[100] flex items-center justify-center cursor-pointer">
        <div class="text-center">
            <div class="w-24 h-24 bg-[#008080] rounded-full flex items-center justify-center mx-auto mb-6 animate-bounce shadow-xl">
                <i data-lucide="play" class="text-white w-12 h-12"></i>
            </div>
            <h2 class="text-3xl font-arima font-black text-[#008080] mb-2">Welcome to Hotelia Express</h2>
            <p class="text-gray-600">Click anywhere to start the order monitor</p>
        </div>
    </div>

    <!-- Particle Canvas -->
    <canvas id="particleCanvas"></canvas>

    <!-- Header -->
    <header class="teal-header rounded-2xl px-8 py-4 flex justify-between items-center shadow-xl">
        <div class="flex items-center gap-4">
            <div class="logo-container">
                <img src="../../assets/images/hotelia.png" alt="Hotelia Logo" class="w-12 h-12 object-contain">
            </div>
            <h1 class="text-3xl font-arima font-black italic tracking-tighter text-white">
                HOTELIA <span class="text-yellow-300">EXPRESS</span>
            </h1>
        </div>
        <div class="clock text-2xl font-bold" id="clock">00:00:00</div>
    </header>

    <!-- Main Content -->
    <div class="flex-1 flex gap-8">
        <!-- Now Serving Column -->
        <div class="w-1/2 flex flex-col h-full overflow-hidden gap-4">
            <div class="serving-header">
                <span class="text-2xl font-black uppercase tracking-widest text-white">Now Serving</span>
                <div class="flex gap-2">
                    <div class="w-3 h-3 bg-white rounded-full animate-ping"></div>
                    <div class="w-3 h-3 bg-white rounded-full animate-ping" style="animation-delay: 0.3s;"></div>
                    <div class="w-3 h-3 bg-white rounded-full animate-ping" style="animation-delay: 0.6s;"></div>
                </div>
            </div>
            
            <div id="serving-container" class="glass-panel custom-scrollbar">
                <!-- Numbers will be inserted here -->
            </div>
        </div>

        <!-- Preparing Column -->
        <div class="w-1/2 flex flex-col gap-4">
            <div class="preparing-header">
                <span class="text-2xl font-black uppercase tracking-widest text-white">Preparing</span>
                <div class="flex gap-2">
                    <div class="w-3 h-3 bg-white rounded-full animate-ping"></div>
                    <div class="w-3 h-3 bg-white rounded-full animate-ping" style="animation-delay: 0.3s;"></div>
                    <div class="w-3 h-3 bg-white rounded-full animate-ping" style="animation-delay: 0.6s;"></div>
                </div>
            </div>
            
            <div class="glass-panel flex-1 p-6 flex flex-col">
                <div id="preparing-container" class="custom-scrollbar">
                    <!-- Numbers will be inserted here -->
                </div>
                
                <!-- Steam Animation and Cooking Waiter -->
                <div class="mt-auto pt-8 flex flex-col items-center">
                    <div class="steam-container">
                        <div class="steam-line"></div>
                        <div class="steam-line"></div>
                        <div class="steam-line"></div>
                    </div>
                    
                    <!-- Cooking Waiter GIF Container -->
                    <div class="cooking-container">
                        <!-- You can replace this with any cooking waiter GIF URL -->
                        <img src="../../assets/images/shet.gif" 
                            alt="Cooking Waiter" 
                            class="cooking-waiter">
                        
                        <!-- Floating food items around the waiter -->
                        <span class="floating-food food-1">🍔</span>
                        <span class="floating-food food-2">🍟</span>
                        <span class="floating-food food-3">🌮</span>
                        <span class="floating-food food-4">🥘</span>
                    </div>
                    
                    <p class="text-3xl font-black italic tracking-widest text-red-500 mt-4 animate-pulse">
                        HOT & FRESH
                    </p>
                    <p class="text-sm text-gray-500 mt-2">Our chefs are preparing your order</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center p-3">
        <p class="text-gray-500 font-bold uppercase tracking-[0.3em] text-xs">
            Please present your order number at the pickup counter
        </p>
    </footer>

    <script src="../../assets/js/lucide.min.js"></script>
   
    <script>
        lucide.createIcons();

        let lastReadyCount = 0;
        // Professional Notification Sound
        const notificationSound = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');

        // Update Monitor Function
        async function updateMonitor() {
            try {
                const res = await fetch('api/get_ready_orders.php?v=' + Date.now());
                const data = await res.json();
                
                if (data.success) {
                    const servingBox = document.getElementById('serving-container');
                    const preparingBox = document.getElementById('preparing-container');

                    // Now Serving: Tables with at least one 'served' item
                    const readyOrders = data.orders.filter(o => 
                        o.order_status.toLowerCase() === 'ready' || 
                        parseInt(o.ready_items_count) > 0
                    );
                    
                    const uniqueReady = Array.from(new Map(readyOrders.map(o => [o.table_number, o])).values());

                    if (uniqueReady.length > lastReadyCount) {
                        notificationSound.play().catch(e => console.log("Sound play failed:", e));
                    }
                    lastReadyCount = uniqueReady.length;

                    servingBox.innerHTML = uniqueReady.map(o => `
                        <div class="number-card pulse-glow" 
                            onclick="markAsPickedUp(${o.order_id}, this)" 
                            style="touch-action: manipulation;">
                            #${o.table_number}
                        </div>
                    `).join('');

                    // Preparing: Orders in progress
                    const preparing = data.orders.filter(o => 
                        o.order_status.toLowerCase() !== 'delivered' &&
                        parseInt(o.ready_items_count) === 0
                    );

                    const uniquePreparing = Array.from(new Map(preparing.map(o => [o.table_number, o])).values());

                    preparingBox.innerHTML = uniquePreparing.map(o => `
                        <div class="prep-number pulse-red">#${o.table_number}</div>
                    `).join('');

                    // Show empty states
                    if (uniqueReady.length === 0) {
                        servingBox.innerHTML = `
                            <div class="col-span-4 flex items-center justify-center text-gray-400 text-xl font-bold">
                                No orders ready for pickup
                            </div>
                        `;
                    }

                    if (uniquePreparing.length === 0) {
                        preparingBox.innerHTML = `
                            <div class="col-span-5 flex items-center justify-center text-gray-400 text-lg font-bold">
                                No orders being prepared
                            </div>
                        `;
                    }
                }
            } catch (e) { 
                console.error("Monitor error:", e); 
            }
        }

        // Update the clock every second
        setInterval(() => { 
            document.getElementById('clock').innerText = new Date().toLocaleTimeString(); 
        }, 1000);

        // Auto-refresh the monitor every 3 seconds
        setInterval(updateMonitor, 3000);
        
        // Run once immediately on load
        updateMonitor();

        // CRITICAL: Unlocks sound when user clicks anywhere on the page
        document.addEventListener('click', () => {
            console.log("Audio Unlocked");
        }, { once: true });

        // Food Particle Animation Logic
        const foodItems = ['🍔', '🍟', '🥤', '🍗', '🍕', '🌮', '🍜', '🍣', '🍱', '🥘']; 
        const canvas = document.getElementById('particleCanvas');
        const ctx = canvas.getContext('2d');

        let particles = [];

        function initParticles() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            particles = [];
            
            // Create 25 floating food items
            for (let i = 0; i < 25; i++) {
                particles.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    size: Math.random() * 30 + 25,
                    speedX: (Math.random() - 0.5) * 0.3,
                    speedY: (Math.random() - 0.5) * 0.3,
                    rotation: Math.random() * 360,
                    spin: (Math.random() - 0.5) * 0.1,
                    char: foodItems[Math.floor(Math.random() * foodItems.length)]
                });
            }
        }

        function drawParticles() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            particles.forEach(p => {
                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate(p.rotation * Math.PI / 180);
                
                ctx.font = `${p.size}px Arial`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                
                // Different colors for different items
                const colors = {
                    '🍔': '#ffcc00',
                    '🍟': '#ff4444',
                    '🥤': '#00d1ff',
                    '🍗': '#ff8800',
                    '🍕': '#ffeb3b',
                    '🌮': '#4caf50',
                    '🍜': '#ff9900',
                    '🍣': '#ff6b6b',
                    '🍱': '#9c88ff',
                    '🥘': '#feca57'
                };
                const glowColor = colors[p.char] || '#008080';

                // Glow effect
                ctx.shadowColor = glowColor;
                ctx.shadowBlur = 20;
                ctx.shadowOffsetX = 0;
                ctx.shadowOffsetY = 0;
                
                ctx.fillStyle = "rgba(255, 255, 255, 0.9)";
                ctx.globalAlpha = 0.7;
                
                ctx.fillText(p.char, 0, 0);
                
                ctx.restore();

                // Movement
                p.x += p.speedX;
                p.y += p.speedY;
                p.rotation += p.spin;

                // Wrap around
                if (p.x < -60) p.x = canvas.width + 60;
                if (p.x > canvas.width + 60) p.x = -60;
                if (p.y < -60) p.y = canvas.height + 60;
                if (p.y > canvas.height + 60) p.y = -60;
            });

            requestAnimationFrame(drawParticles);
        }

        window.addEventListener('resize', initParticles);
        initParticles();
        drawParticles();

        // Mark as Picked Up
        async function markAsPickedUp(orderId, element) {
            // Visual feedback
            if (element) {
                element.style.transition = "all 0.3s ease";
                element.style.opacity = "0";
                element.style.transform = "scale(0.5)";
                setTimeout(() => element.remove(), 300);
            }

            try {
                const res = await fetch('api/pickup_items.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId })
                });

                const data = await res.json();
                if (!data.success) {
                    updateMonitor();
                }
            } catch (error) {
                console.error("Pickup error:", error);
                updateMonitor();
            }
        }

        // Load and play theme song
        const themeSong = new Audio('../../assets/sounds/Hotelia Express (Fast and Fresh).mp3');
        themeSong.loop = true;
        themeSong.volume = 0.3;

        // Auto-play theme
        async function playTheme() {
            try {
                await themeSong.play();
                console.log("Hotelia Theme is now playing automatically!");
            } catch (err) {
                console.warn("Autoplay blocked by browser. Music will start on the first click.");
                document.addEventListener('click', () => {
                    themeSong.play();
                }, { once: true });
            }
        }

        playTheme();

        // Start overlay click handler
        document.getElementById('start-overlay').addEventListener('click', function() {
            this.style.display = 'none';
            themeSong.play();
            updateMonitor();
        });
    </script>
</body>
</html>