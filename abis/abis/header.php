<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ABIS | Performance & Safety Excellence</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; scroll-behavior: smooth; }
        .reveal { opacity: 0; transform: translateY(30px); transition: all 0.7s cubic-bezier(0.2, 0.9, 0.4, 1.1); }
        .reveal.active { opacity: 1; transform: translateY(0); }
        .bg-abis-orange { background-color: #e87d34; }
        .text-abis-orange { color: #e87d34; }
        .bg-abis-blue { background-color: #0d1f2d; }
        .text-abis-blue { color: #0d1f2d; }
        .bg-abis-gray { background-color: #f4f7f6; }
        .border-abis-orange { border-color: #e87d34; }
        .hover\:bg-abis-orange:hover { background-color: #e87d34; }
        .hover\:text-abis-orange:hover { color: #e87d34; }
        .btn-primary {
            background-color: #e87d34;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(232,125,52,0.3);
        }
        .btn-primary:hover {
            background-color: #d46a28;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(232,125,52,0.4);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 30px -12px rgba(0,0,0,0.15);
            border-color: #e87d34;
        }
        /* Location Tabs & Grid Styles */
        .locations-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 32px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .tab-btn {
            padding: 10px 24px;
            border: 2px solid #e87d34;
            background: white;
            color: #0d1f2d;
            font-weight: 600;
            font-size: 13px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .tab-btn:hover {
            background: #e87d34;
            color: white;
            box-shadow: 0 8px 24px rgba(232, 125, 52, 0.25);
            transform: translateY(-2px);
        }
        .tab-btn.active {
            background: #e87d34;
            color: white;
            box-shadow: 0 8px 24px rgba(232, 125, 52, 0.35);
        }
        .location-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 2px solid transparent;
            transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            cursor: pointer;
            height: 100%;
            display: flex;
            flex-direction: column;
            animation: slideInCard 0.5s ease forwards;
        }
        .location-card.hq { border-top: 4px solid #d4af37; }
        .location-card.regional { border-top: 4px solid #00a8e8; }
        .location-card.project { border-top: 4px solid #6ec31c; }
        .location-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.15);
            border-color: #e87d34;
        }
        .location-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        .location-card:hover .location-image { transform: scale(1.05); }
        .location-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(232, 125, 52, 0.95);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .location-info {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .location-name {
            font-size: 18px;
            font-weight: bold;
            color: #0d1f2d;
            margin-bottom: 12px;
        }
        .location-detail {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .location-detail strong {
            color: #e87d34;
            min-width: 90px;
        }
        .location-card.hidden { display: none; }
        @keyframes slideInCard {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        /* Location Modal Styles - Enhanced Animations */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(13, 31, 45, 0);
            backdrop-filter: blur(0px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.4s ease-out, background 0.4s ease-out, backdrop-filter 0.4s ease-out;
            pointer-events: none;
        }
        .modal-overlay.active {
            display: flex;
            opacity: 1;
            background: rgba(13, 31, 45, 0.7);
            backdrop-filter: blur(4px);
            pointer-events: auto;
        }
        .location-modal {
            background: white;
            border-radius: 12px;
            max-width: 580px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
            opacity: 0;
            transform: scale(0.88) translateY(-40px);
            filter: blur(8px);
            animation: modalSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        .modal-overlay.active .location-modal {
            animation: modalSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        .location-modal.closing {
            animation: modalSlideOut 0.3s cubic-bezier(0.7, 0, 1, 0.3) forwards !important;
        }
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.88) translateY(-40px);
                filter: blur(8px);
            }
            70% {
                transform: scale(1.02) translateY(5px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
                filter: blur(0px);
            }
        }
        @keyframes modalSlideOut {
            from {
                opacity: 1;
                transform: scale(1) translateY(0);
                filter: blur(0px);
            }
            to {
                opacity: 0;
                transform: scale(0.88) translateY(20px);
                filter: blur(8px);
            }
        }
        .modal-header {
            position: relative;
            height: 260px;
            overflow: hidden;
            border-radius: 12px 12px 0 0;
            opacity: 0;
            animation: headerFadeIn 0.7s cubic-bezier(0.34, 1.56, 0.64, 1) 0.15s forwards;
        }
        .modal-header img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform: scale(0.95);
        }
        .modal-overlay.active .modal-header img {
            transform: scale(1);
        }
        @keyframes headerFadeIn {
            from {
                opacity: 0;
                filter: blur(10px);
            }
            to {
                opacity: 1;
                filter: blur(0px);
            }
        }
        .modal-header-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(13,31,45,0.3), rgba(232,125,52,0.2)), linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.5));
        }
        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 44px;
            height: 44px;
            background: rgba(255,255,255,0.95);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            z-index: 10;
            font-size: 24px;
            font-weight: 300;
            color: #0d1f2d;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            opacity: 0;
            transform: scale(0.5) rotate(-45deg);
            animation: buttonPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.35s forwards;
        }
        .modal-close:hover {
            background: white;
            transform: scale(1.15) rotate(90deg);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        @keyframes buttonPop {
            from {
                opacity: 0;
                transform: scale(0.5) rotate(-45deg);
            }
            70% {
                transform: scale(1.1) rotate(15deg);
            }
            to {
                opacity: 1;
                transform: scale(1) rotate(0deg);
            }
        }
        .modal-title {
            position: absolute;
            bottom: 20px;
            left: 24px;
            right: 70px;
            color: white;
            z-index: 5;
        }
        .modal-title h2 {
            font-size: 32px;
            font-weight: 800;
            margin: 0;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }
        .modal-title p {
            font-size: 13px;
            margin: 0;
            opacity: 0.95;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .modal-badge {
            display: inline-block;
            background: #e87d34;
            color: white;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(232, 125, 52, 0.3);
        }
        .modal-content {
            padding: 36px 32px;
            background: white;
        }
        .modal-description {
            background: linear-gradient(135deg, #f4f7f6 0%, #ffffff 100%);
            padding: 18px 20px;
            border-radius: 10px;
            border-left: 4px solid #e87d34;
            line-height: 1.7;
            color: #444;
            font-size: 14px;
            margin-bottom: 28px;
            opacity: 0;
            animation: descriptionSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.25s forwards;
        }
        @keyframes descriptionSlideIn {
            from {
                opacity: 0;
                transform: translateY(15px);
                filter: blur(4px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
                filter: blur(0px);
            }
        }
        .modal-section {
            margin-bottom: 28px;
            opacity: 0;
            animation: sectionSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        .modal-section:nth-child(1) { animation-delay: 0.3s; }
        .modal-section:nth-child(2) { animation-delay: 0.38s; }
        .modal-section:nth-child(3) { animation-delay: 0.46s; }
        .modal-section:nth-child(4) { animation-delay: 0.54s; }
        .modal-section:nth-child(5) { animation-delay: 0.62s; }
        .modal-section:last-child {
            margin-bottom: 0;
        }
        @keyframes sectionSlideIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
                filter: blur(4px);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
                filter: blur(0px);
            }
        }
        .modal-section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            color: #0d1f2d;
            margin-bottom: 14px;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .modal-info-item {
            background: linear-gradient(135deg, #f4f7f6 0%, rgba(244,247,246,0.5) 100%);
            padding: 16px;
            border-radius: 10px;
            border: 1px solid rgba(232, 125, 52, 0.1);
            border-left: 4px solid #e87d34;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            opacity: 0;
            transform: scale(0.95);
            animation: itemPulse 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        .modal-section:nth-child(1) .modal-info-item:nth-child(1) { animation-delay: 0.35s; }
        .modal-section:nth-child(1) .modal-info-item:nth-child(2) { animation-delay: 0.4s; }
        .modal-section:nth-child(2) .modal-info-item { animation-delay: 0.48s; }
        .modal-section:nth-child(3) .modal-info-item { animation-delay: 0.56s; }
        .modal-section:nth-child(4) .modal-info-item:nth-child(1) { animation-delay: 0.64s; }
        .modal-section:nth-child(4) .modal-info-item:nth-child(2) { animation-delay: 0.7s; }
        .modal-info-item:hover {
            box-shadow: 0 6px 16px rgba(232, 125, 52, 0.15);
            transform: translateY(-3px);
            border-left-color: #d46a28;
        }
        @keyframes itemPulse {
            from {
                opacity: 0;
                transform: scale(0.92) translateY(10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        .modal-info-label {
            font-size: 10px;
            color: #888;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }
        .modal-info-value {
            font-size: 16px;
            font-weight: 700;
            color: #0d1f2d;
            word-break: break-word;
        }
        .modal-info-value a {
            color: #e87d34;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        .modal-info-value a:hover {
            color: #d46a28;
            text-decoration: underline;
        }
    </style>
    <link rel="stylesheet" href="assets/responsive-system.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        abis: { orange: '#e87d34', blue: '#0d1f2d', gray: '#f4f7f6' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-abis-gray text-abis-blue">

<!-- Top bar (لغة + شعار السلامة) -->
<div class="bg-abis-blue text-white py-2.5 px-4 text-[11px] font-bold uppercase tracking-widest flex justify-between items-center">
    <span class="flex items-center gap-2"><span class="text-abis-orange"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg></span> Safety First. Quality Always.</span>
    <div class="flex gap-5">
        <a href="#" class="hover:text-abis-orange transition-colors">English</a>
        <a href="#" class="hover:text-abis-orange transition-colors">العربية</a>
    </div>
</div>

<!-- Navigation الرئيسي (مستوحى من أرامكو) -->
<nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20 items-center">
            <div class="flex items-center gap-3">
                <a href="index.php" class="flex items-center gap-3">
                    <img src="images/brand/abis.png" alt="ABIS logo" class="h-12 w-auto" onerror="this.src='https://placehold.co/50x50?text=ABIS'">
                </a>
            </div>
            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="index.php" class="text-sm font-semibold hover:text-abis-orange transition-colors border-b-2 border-transparent hover:border-abis-orange pb-1">Home</a>
                <a href="about.php" class="text-sm font-semibold hover:text-abis-orange transition-colors border-b-2 border-transparent hover:border-abis-orange pb-1">About</a>
                <a href="services.php" class="text-sm font-semibold hover:text-abis-orange transition-colors border-b-2 border-transparent hover:border-abis-orange pb-1">Services</a>
                <a href="news.php" class="text-sm font-semibold hover:text-abis-orange transition-colors border-b-2 border-transparent hover:border-abis-orange pb-1">News</a>
                <a href="careers.php" class="text-sm font-semibold hover:text-abis-orange transition-colors border-b-2 border-transparent hover:border-abis-orange pb-1">Careers</a>
                <a href="contact.php" class="text-sm font-semibold hover:text-abis-orange transition-colors border-b-2 border-transparent hover:border-abis-orange pb-1">Contact</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="bg-abis-blue text-white px-5 py-2.5 rounded-lg font-bold text-sm shadow-md hover:bg-abis-orange transition-all">Portal</a>
                <?php else: ?>
                    <a href="login.php" class="bg-abis-orange text-white px-5 py-2.5 rounded-lg font-bold text-sm shadow-md hover:bg-abis-blue transition-all">Login</a>
                <?php endif; ?>
            </div>
            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button id="mobile-menu-button" class="text-abis-blue p-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
            </div>
        </div>
    </div>
    <!-- Mobile dropdown -->
    <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-200 p-5 space-y-4 shadow-lg">
        <a href="index.php" class="block font-bold text-abis-blue">Home</a>
        <a href="about.php" class="block font-bold text-abis-blue">About</a>
        <a href="services.php" class="block font-bold text-abis-blue">Services</a>
        <a href="news.php" class="block font-bold text-abis-blue">News</a>
        <a href="careers.php" class="block font-bold text-abis-blue">Careers</a>
        <a href="contact.php" class="block font-bold text-abis-blue">Contact</a>
        <a href="login.php" class="block bg-abis-orange text-white px-4 py-2 rounded-lg font-bold text-center">Login</a>
    </div>
</nav>