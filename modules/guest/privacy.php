<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/theme/theme.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms, Policies & Privacy — Hotelia</title>
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <link rel="stylesheet" href="../../assets/css/input.css">
</head>

<body class="<?php echo theme_class(); ?> theme-transition min-h-screen hotelia-page-colors font-mulish">

    <?php include __DIR__ . '/../../includes/components/header.php'; ?>

    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="max-w-3xl mx-auto">

            <div class="border-b border-light-border dark:border-dark-border pb-8 mb-12">
                <h1 class="text-3xl md:text-4xl font-arima font-bold hotelia-label-colors mb-2">Terms of Service & Guest Policies</h1>
                <p class="hotelia-label-secondary-colors text-sm uppercase tracking-widest">Last Updated: December 27, 2025</p>
            </div>

            <div class="space-y-12">

                <div>
                    <p class="hotelia-label-secondary-colors leading-relaxed">
                        By accessing this website and utilizing our booking services, you agree to be bound by the following terms and conditions. These policies are designed to ensure a safe, secure, and transparent environment for both Hotelia and our valued guests.
                    </p>
                </div>

                <section class="space-y-4">
                    <div class="flex items-center gap-3 text-brand-teal">
                        <i data-lucide="user-check" class="w-6 h-6"></i>
                        <h2 class="text-xl font-bold hotelia-label-colors uppercase tracking-tight">1. Guest Identification & Check-in</h2>
                    </div>
                    <div class="pl-9 space-y-3 hotelia-label-secondary-colors">
                        <p><strong>Verification:</strong> A valid government-issued photo ID (Passport, Driver’s License, or National ID) is mandatory upon arrival. We reserve the right to refuse entry without proper identification.</p>
                        <p><strong>Timing:</strong> Standard check-in is at 2:00 PM. Check-out is strictly at 12:00 PM. Late check-outs result in a fee equivalent to 50% of the room rate to allow our cleaning teams to prepare for incoming guests.</p>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center gap-3 text-brand-teal">
                        <i data-lucide="calendar-x" class="w-6 h-6"></i>
                        <h2 class="text-xl font-bold hotelia-label-colors uppercase tracking-tight">2. Non-Extendable Stay Policy</h2>
                    </div>
                    <div class="pl-9 space-y-3 hotelia-label-secondary-colors">
                        <p><strong>Fixed Duration:</strong> All reservations are final for the specific dates selected during checkout. Due to our strict "Occupancy Logic" which prevents overbooking, we <strong>cannot grant stay extensions</strong> once a room has been reserved by another guest for the following date.</p>
                        <p><strong>No Reservation Displacement:</strong> To protect the integrity of our system, we do not cancel or move existing reservations to accommodate a guest who wishes to stay longer. If you require additional nights, you must create a <strong>new, separate booking</strong> subject to real-time availability.</p>
                        <p><strong>Mandatory Departure:</strong> Guests are required to vacate the room by the 12:00 PM check-out time. Failure to do so will result in an automated overstay fee as we must prioritize the arrival of the next confirmed guest.</p>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center gap-3 text-brand-teal">
                        <i data-lucide="shield-alert" class="w-6 h-6"></i>
                        <h2 class="text-xl font-bold hotelia-label-colors uppercase tracking-tight">3. Occupancy & Safety Regulations</h2>
                    </div>
                    <div class="pl-9 space-y-3 hotelia-label-secondary-colors">
                        <p><strong>Maximum Capacity:</strong> For your safety and to comply with local fire regulations, each room type has a strict maximum capacity. Exceeding this count without prior authorization is grounds for immediate reservation cancellation without refund.</p>
                        <p><strong>Timeline Security:</strong> Our system uses an "Occupancy Logic" bottleneck scan. We do not overbook. If our system detects that a single night within your stay is fully occupied, the entire stay will be restricted to protect the integrity of existing bookings.</p>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center gap-3 text-brand-teal">
                        <i data-lucide="credit-card" class="w-6 h-6"></i>
                        <h2 class="text-xl font-bold hotelia-label-colors uppercase tracking-tight">4. Payment & Transaction Security</h2>
                    </div>
                    <div class="pl-9 space-y-3 hotelia-label-secondary-colors">
                        <p><strong>Guarantee:</strong> A room is only considered "booked" once a valid payment reference number (e.g., GCash, PayMaya) is submitted and verified. Rooms are held in a "Pending" state for a maximum of 15 minutes during the checkout process.</p>
                        <p><strong>Integrity:</strong> We store only the reference numbers for verification. We do not store or have access to your digital wallet passwords or sensitive financial credentials.</p>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center gap-3 text-brand-teal">
                        <i data-lucide="hammer" class="w-6 h-6"></i>
                        <h2 class="text-xl font-bold hotelia-label-colors uppercase tracking-tight">5. Property Liability & Conduct</h2>
                    </div>
                    <div class="pl-9 space-y-3 hotelia-label-secondary-colors">
                        <p><strong>Damages:</strong> The registered guest is financially responsible for any damage to hotel property, including furniture, electronics, and linens, beyond normal wear and tear.</p>
                        <p><strong>Prohibited Acts:</strong> Illegal substances, unauthorized parties, and smoking inside non-smoking rooms are strictly prohibited. Violation results in immediate eviction and a sanitation fee.</p>
                    </div>
                </section>

                <section class="space-y-4 border-t border-light-border dark:border-dark-border pt-10">
                    <div class="flex items-center gap-3 text-brand-teal">
                        <i data-lucide="eye-off" class="w-6 h-6"></i>
                        <h2 class="text-xl font-bold hotelia-label-colors uppercase tracking-tight">5. Data Privacy Commitment</h2>
                    </div>
                    <div class="pl-9 space-y-3 hotelia-label-secondary-colors">
                        <p><strong>Collection:</strong> We collect your name, email, and phone number solely to manage your stay. This data is protected by industry-standard encryption.</p>
                        <p><strong>Retention:</strong> Personal data is stored only for as long as necessary to fulfill booking requirements or legal tax obligations. We do not share or sell your data to third-party marketing firms.</p>
                    </div>
                </section>

            </div>

            <div class="mt-20 text-center">
                <p class="text-xs hotelia-label-secondary-colors mb-8 uppercase tracking-widest italic">
                    By proceeding with your reservation, you certify that you are at least 18 years of age and agree to all terms stated above.
                </p>
                <a href="dashboard.php" class="inline-flex items-center gap-2 hotelia-label-colors hover:text-brand-teal transition-colors font-bold uppercase text-sm">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Return to Dashboard
                </a>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../../includes/components/footer.php'; ?>

    <script src="../../assets/js/lucide.min.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>

</html>