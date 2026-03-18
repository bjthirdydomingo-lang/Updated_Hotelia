<?php // includes/components/footer.php 
?>

<footer class="hotelia-page-colors transition-colors duration-300">
    <div class="container mx-auto px-4 sm:px-6 py-8 sm:py-12">
        <!-- Main Footer Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
            <!-- Brand Section -->
            <div class="space-y-3 sm:space-y-4 text-center sm:text-left">
                <div class="flex items-center justify-center sm:justify-start space-x-2 sm:space-x-3">
                    <img src="../../assets/images/logo.svg" alt="Hotelia" class="w-7 h-7 sm:w-8 sm:h-8">
                    <h3 class="text-lg sm:text-xl font-arima font-black text-brand-teal">
                        Hotelia
                    </h3>
                </div>
                <p class="text-sm hotelia-label-secondary-colors leading-relaxed max-w-xs mx-auto sm:max-w-none sm:mx-0">
                    Experience luxury hospitality at its finest. We provide exceptional service and unforgettable experiences in the heart of the city.
                </p>

                <!-- Social Media Links -->
                <div class="flex justify-center sm:justify-start space-x-2 sm:space-x-3">
                    <a href="#" aria-label="Facebook"
                        class="p-1.5 sm:p-2 rounded-lg hotelia-btn-primary-colors
                               hover:scale-105 hotelia-transition">
                        <i data-lucide="facebook" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                    </a>
                    <a href="#" aria-label="Twitter"
                        class="p-1.5 sm:p-2 rounded-lg hotelia-btn-primary-colors
                               hover:scale-105 hotelia-transition">
                        <i data-lucide="twitter" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                    </a>
                    <a href="#" aria-label="Instagram"
                        class="p-1.5 sm:p-2 rounded-lg hotelia-btn-primary-colors
                               hover:scale-105 hotelia-transition">
                        <i data-lucide="instagram" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                    </a>
                    <a href="#" aria-label="LinkedIn"
                        class="p-1.5 sm:p-2 rounded-lg hotelia-btn-primary-colors
                               hover:scale-105 hotelia-transition">
                        <i data-lucide="linkedin" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="space-y-3 sm:space-y-4 text-center sm:text-left">
                <h4 class="text-sm sm:text-base font-semibold hotelia-label-colors">
                    Quick Links
                </h4>
                <ul class="space-y-1.5 sm:space-y-2">
                    <li>
                        <a href="#home"
                            class="text-sm hotelia-label-secondary-colors 
                                  hover:text-brand-teal hotelia-transition inline-block">
                            Home
                        </a>
                    </li>
                    <li>
                        <a href="#rooms"
                            class="text-sm hotelia-label-secondary-colors 
                                  hover:text-brand-teal hotelia-transition inline-block">
                            Rooms & Suites
                        </a>
                    </li>
                    <li>
                        <a href="#facilities"
                            class="text-sm hotelia-label-secondary-colors 
                                  hover:text-brand-teal hotelia-transition inline-block">
                            Facilities
                        </a>
                    </li>
                    <li>
                        <a href="gallery.php"
                            class="text-sm hotelia-label-secondary-colors 
                                  hover:text-brand-teal hotelia-transition inline-block">
                            Gallery
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Our Facilities -->
            <div class="space-y-3 sm:space-y-4 text-center sm:text-left">
                <h4 class="text-sm sm:text-base font-semibold hotelia-label-colors">
                    Our Facilities
                </h4>
                <ul class="space-y-1.5 sm:space-y-2">
                    <li>
                        <a href="#"
                            class="text-sm hotelia-label-secondary-colors 
                                  hover:text-brand-teal hotelia-transition inline-block">
                            Valet Parking
                        </a>
                    </li>
                    <li>
                        <a href="#"
                            class="text-sm hotelia-label-secondary-colors 
                                  hover:text-brand-teal hotelia-transition inline-block">
                            On-site Restaurant
                        </a>
                    </li>
                    <li>
                        <a href="#"
                            class="text-sm hotelia-label-secondary-colors 
                                  hover:text-brand-teal hotelia-transition inline-block">
                            Fitness Center
                        </a>
                    </li>
                    <li>
                        <a href="#"
                            class="text-sm hotelia-label-secondary-colors 
                                  hover:text-brand-teal hotelia-transition inline-block">
                            24/7 Front Desk
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Contact Us -->
            <div id="contact" class="space-y-3 sm:space-y-4 text-center sm:text-left">
                <h4 class="text-sm sm:text-base font-semibold hotelia-label-colors">
                    Contact Us
                </h4>
                <div class="space-y-2.5 sm:space-y-3">
                    <!-- Address -->
                    <div class="flex items-start justify-center sm:justify-start space-x-2">
                        <i data-lucide="map-pin" class="w-4 h-4 text-brand-teal mt-0.5 flex-shrink-0"></i>
                        <div class="text-sm hotelia-label-secondary-colors text-center sm:text-left">
                            <p>123 Luxury Avenue</p>
                            <p>Downtown District</p>
                            <p>City, State 12345</p>
                        </div>
                    </div>

                    <!-- Phone -->
                    <div class="flex items-center justify-center sm:justify-start space-x-2">
                        <i data-lucide="phone" class="w-4 h-4 text-brand-teal flex-shrink-0"></i>
                        <a href="tel:+1234567890"
                            class="text-sm hotelia-label-secondary-colors 
                                  hover:text-brand-teal hotelia-transition">
                            +1 (234) 567-8900
                        </a>
                    </div>

                    <!-- Email -->
                    <div class="flex items-center justify-center sm:justify-start space-x-2">
                        <i data-lucide="mail" class="w-4 h-4 text-brand-teal flex-shrink-0"></i>
                        <a href="mailto:info@hotelia.com"
                            class="text-sm hotelia-label-secondary-colors 
                                  hover:text-brand-teal hotelia-transition break-all sm:break-normal">
                            info@hotelia.com
                        </a>
                    </div>

                    <!-- Hours -->
                    <div class="flex items-center justify-center sm:justify-start space-x-2">
                        <i data-lucide="clock" class="w-4 h-4 text-brand-teal flex-shrink-0"></i>
                        <div class="text-sm hotelia-label-secondary-colors">
                            24/7 Reception
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="mt-6 sm:mt-8 pt-4 sm:pt-6 hotelia-border-colors border-t
                    flex flex-col sm:flex-row justify-between items-center space-y-3 sm:space-y-0">
            <!-- Copyright -->
            <div class="text-xs sm:text-sm hotelia-label-secondary-colors text-center sm:text-left">
                &copy; <?= date('Y') ?> Hotelia. All rights reserved. |
                <a href="privacy.php"
                    class="hover:text-brand-teal hotelia-transition mx-1">
                    Privacy Policy
                </a> |
                <a href="terms.php"
                    class="hover:text-brand-teal hotelia-transition mx-1">
                    Terms of Service
                </a>
            </div>

            <!-- Made by -->
            <div class="flex items-center space-x-2 text-xs sm:text-sm hotelia-label-secondary-colors">
                <span>Made with</span>
                <i data-lucide="heart" class="w-3 h-3 text-red-500 animate-pulse"></i>
                <span>by</span>
                <i data-lucide="code" class="w-3 h-3 text-brand-teal"></i>
                <span class="font-medium text-brand-teal">Lokidev</span>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop"
    class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 p-2.5 sm:p-3 
           rounded-full hotelia-btn-primary-colors
           shadow-lg hover:shadow-xl hover:scale-110
           hotelia-transition opacity-0 invisible z-40"
    aria-label="Back to top">
    <i data-lucide="arrow-up" class="w-4 h-4 sm:w-5 sm:h-5"></i>
</button>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Back to Top Button
        const backToTopButton = document.getElementById('backToTop');
        if (backToTopButton) {
            // Show/hide button based on scroll position
            window.addEventListener('scroll', function() {
                if (window.scrollY > 300) {
                    backToTopButton.classList.remove('opacity-0', 'invisible');
                    backToTopButton.classList.add('opacity-100', 'visible');
                } else {
                    backToTopButton.classList.add('opacity-0', 'invisible');
                    backToTopButton.classList.remove('opacity-100', 'visible');
                }
            });

            // Smooth scroll to top
            backToTopButton.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }

        // Newsletter Form
        const newsletterForm = document.querySelector('footer form');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const emailInput = e.target.querySelector('input[type="email"]');
                const email = emailInput.value.trim();

                if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    // Show success message (you can customize this)
                    const button = e.target.querySelector('button');
                    const originalText = button.innerHTML;

                    button.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span>Subscribed!</span>';
                    button.classList.add('bg-green-500', 'hover:bg-green-600');
                    button.classList.remove('bg-hotelia-teal', 'hover:bg-hotelia-teal-light');

                    // Re-initialize lucide icons
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }

                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.classList.remove('bg-green-500', 'hover:bg-green-600');
                        button.classList.add('bg-hotelia-teal', 'hover:bg-hotelia-teal-light');
                        emailInput.value = '';

                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    }, 2000);
                } else {
                    // Show error for invalid email
                    emailInput.classList.add('border-red-500', 'focus:ring-red-500');
                    setTimeout(() => {
                        emailInput.classList.remove('border-red-500', 'focus:ring-red-500');
                    }, 3000);
                }
            });
        }

        // Add hover effects for social media icons
        const socialIcons = document.querySelectorAll('footer a[aria-label]');
        socialIcons.forEach(icon => {
            icon.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.05)';
            });

            icon.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Intersection Observer for footer animations (optional)
        if ('IntersectionObserver' in window) {
            const footerObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-in');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            const footerSections = document.querySelectorAll('footer > div > div > div');
            footerSections.forEach(section => {
                footerObserver.observe(section);
            });
        }
    });
</script>

<style>
    /* Optional: Add a subtle fade-in animation */
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fade-in 0.6s ease-out forwards;
    }

    /* Ensure proper text wrapping on mobile */
    @media (max-width: 640px) {
        footer a[href^="mailto:"] {
            word-break: break-all;
        }
    }
</style>