<?php
require_once 'check_session.php';
$user = checkUserSession();
$pageTitle = 'Home - IGM';

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
if ($lang == 'ar') {
    include 'lang/ar.php';
    $text = $arabic;
} else {
    $text = [];
}
?>
<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <section class="hero">
            <div class="hero-content">
                <h1><?php echo isset($text['welcome_title']) ? $text['welcome_title'] : 'Welcome to IGM'; ?></h1>
                <p><?php echo isset($text['welcome_desc']) ? $text['welcome_desc'] : 'A smart learning platform powered by artificial intelligence technologies that supports students in managing their time and improving academic performance.'; ?></p>
                <p><?php echo isset($text['welcome_more']) ? $text['welcome_more'] : 'The platform seeks to provide an integrated learning environment that combines effective organization, personalized learning, and development opportunities, ensuring enhanced understanding, reduced distraction, and increased student productivity.'; ?></p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="registration.php<?php echo isset($_GET['lang']) ? '?lang='.$_GET['lang'] : ''; ?>" class="cta-button">
                        <?php echo isset($text['join_us']) ? $text['join_us'] : 'Join Us'; ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path stroke="white" stroke-width="2" d="M13.5 5L20.5 12L13.5 19M3.5 12L20 12"/></svg>
                    </a>
                <?php else: ?>
                    <a href="dashboard.php<?php echo isset($_GET['lang']) ? '?lang='.$_GET['lang'] : ''; ?>" class="cta-button">
                        <?php echo isset($text['go_to_dashboard']) ? $text['go_to_dashboard'] : 'Go to Dashboard'; ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path stroke="white" stroke-width="2" d="M13.5 5L20.5 12L13.5 19M3.5 12L20 12"/></svg>
                    </a>
                <?php endif; ?>
            </div>
            <div class="hero-image">
                <img src="https://framerusercontent.com/images/OnwVG4YqYfbCzufpf20vI97UHvA.png" alt="IGM Platform">
            </div>
        </section>

        <section class="features" id="work">
            <h2><?php echo isset($text['features_title']) ? $text['features_title'] : 'Features of IGM'; ?></h2>
            <div class="features-grid">
                <div class="feature-card">
                    <h4><?php echo isset($text['feature1']) ? $text['feature1'] : 'Interactive Learning'; ?></h4>
                    <p><?php echo isset($text['feature1_desc']) ? $text['feature1_desc'] : 'We turn studying into a fun and engaging experience through game-based learning instead of boring memorization.'; ?></p>
                </div>
                <div class="feature-card">
                    <h4><?php echo isset($text['feature2']) ? $text['feature2'] : 'Study Timetables'; ?></h4>
                    <p><?php echo isset($text['feature2_desc']) ? $text['feature2_desc'] : 'Personalized schedules to help students organize their time and track progress.'; ?></p>
                </div>
                <div class="feature-card">
                    <h4><?php echo isset($text['feature3']) ? $text['feature3'] : 'Hackathon Hub'; ?></h4>
                    <p><?php echo isset($text['feature3_desc']) ? $text['feature3_desc'] : 'A dedicated space for challenges and collaboration, inspiring creativity and teamwork.'; ?></p>
                </div>
                <div class="feature-card">
                    <h4><?php echo isset($text['feature4']) ? $text['feature4'] : 'Smart Evaluation System'; ?></h4>
                    <p><?php echo isset($text['feature4_desc']) ? $text['feature4_desc'] : 'Students must achieve at least 80% to move to the next level, ensuring true understanding before progress.'; ?></p>
                </div>
                <div class="feature-card">
                    <h4><?php echo isset($text['feature5']) ? $text['feature5'] : 'Dynamic Question Bank'; ?></h4>
                    <p><?php echo isset($text['feature5_desc']) ? $text['feature5_desc'] : 'A randomized question system that prevents repetition and encourages critical thinking.'; ?></p>
                </div>
                <div class="feature-card">
                    <h4><?php echo isset($text['feature6']) ? $text['feature6'] : 'Motivational Breaks'; ?></h4>
                    <p><?php echo isset($text['feature6_desc']) ? $text['feature6_desc'] : 'Short breaks with light activities and uplifting messages to keep focus and energy high.'; ?></p>
                </div>
            </div>
        </section>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // سكربتات مشتركة
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.getElementById('mainContent');
        const userInfo = document.getElementById('userInfo');
        const userDropdown = document.getElementById('userDropdown');
        const logoutBtn = document.getElementById('logoutBtn');

        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            mainContent.classList.toggle('sidebar-open');
            sidebarOverlay.classList.toggle('show');
        });

        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            mainContent.classList.remove('sidebar-open');
            sidebarOverlay.classList.remove('show');
        });

        userInfo.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });

        document.addEventListener('click', function() {
            userDropdown.classList.remove('show');
        });

        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'logout.php';
        });

        // إشعارات
        const notificationBell = document.getElementById('notificationBell');
        const notificationDropdown = document.getElementById('notificationDropdown');
        if (notificationBell) {
            notificationBell.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('show');
            });
        }
    </script>
</body>
</html>