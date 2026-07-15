<?php
require_once 'check_session.php';
$user = checkUserSession();
$pageTitle = 'About Us - IGM';

// تحديد اللغة
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
if ($lang == 'ar') {
    include 'lang/ar.php';
    $text = $arabic;
} else {
    $text = []; // سنستخدم النصوص الإنجليزية المضمنة
}
?>
<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- About Section -->
        <section class="aboutus-section">
            <div class="aboutus-container">
                <h1 class="aboutus-section-title">
                    <?php echo isset($text['about_title']) ? $text['about_title'] : 'Who We Are at IGM Initiative'; ?>
                </h1>
                <div class="aboutus-english-text">
                    <p>
                        <?php echo isset($text['about_description']) ? $text['about_description'] : 'We are five creative female students from King Faisal University, majoring in Information Systems. We launched an innovative graduation project that combines smart learning, time management, and practical training, under the slogan: "Learn Smarter... Not Harder."'; ?>
                    </p>
                </div>
            </div>
        </section>

        <!-- Vision Section -->
        <section class="aboutus-section">
            <div class="aboutus-container">
                <h2 class="aboutus-section-title">
                    <?php echo isset($text['vision_title']) ? $text['vision_title'] : 'Our Vision'; ?>
                </h2>
                <div class="aboutus-vision-text">
                    "<?php echo isset($text['vision_text']) ? $text['vision_text'] : 'For IGM to be the companion of every ambitious student, where time is optimally invested and preparation is done intelligently, not with excessive effort.'; ?>"
                </div>
            </div>
        </section>

        <!-- Story Section -->
        <section class="aboutus-section aboutus-story-section">
            <div class="aboutus-container">
                <div class="aboutus-story-wrapper">
                    <div class="aboutus-story-left">
                        <h2 class="aboutus-section-title">
                            <?php echo isset($text['story_title']) ? $text['story_title'] : 'Story of the Idea'; ?>
                        </h2>
                        <div class="aboutus-story-intro">
                            <p>
                                <?php echo isset($text['story_intro']) ? $text['story_intro'] : 'IGM was born from the real struggles students face while studying, especially those who struggle with learning or time management. We didn\'t want an ordinary solution, but rather a three-dimensional platform that combines:'; ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="aboutus-story-right">
                        <div class="aboutus-story-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                    </div>
                </div>
                
                <div class="aboutus-features-list">
                    <div class="aboutus-feature-item">
                        <div class="aboutus-feature-number">01</div>
                        <div class="aboutus-feature-content">
                            <h4><?php echo isset($text['feature1_title']) ? $text['feature1_title'] : 'Discovering each student\'s unique learning style'; ?></h4>
                            <p><?php echo isset($text['feature1_desc']) ? $text['feature1_desc'] : 'Personalized learning paths tailored to individual needs'; ?></p>
                        </div>
                    </div>
                    
                    <div class="aboutus-feature-item">
                        <div class="aboutus-feature-number">02</div>
                        <div class="aboutus-feature-content">
                            <h4><?php echo isset($text['feature2_title']) ? $text['feature2_title'] : 'Customized smart tests'; ?></h4>
                            <p><?php echo isset($text['feature2_desc']) ? $text['feature2_desc'] : 'AI-powered questions adapted to learning preferences'; ?></p>
                        </div>
                    </div>
                    
                    <div class="aboutus-feature-item">
                        <div class="aboutus-feature-number">03</div>
                        <div class="aboutus-feature-content">
                            <h4><?php echo isset($text['feature3_title']) ? $text['feature3_title'] : 'Detailed performance analysis'; ?></h4>
                            <p><?php echo isset($text['feature3_desc']) ? $text['feature3_desc'] : 'Comprehensive insights into progress and areas for improvement'; ?></p>
                        </div>
                    </div>
                    
                    <div class="aboutus-feature-item">
                        <div class="aboutus-feature-number">04</div>
                        <div class="aboutus-feature-content">
                            <h4><?php echo isset($text['feature4_title']) ? $text['feature4_title'] : 'Courses & Hackathons Newsletters'; ?></h4>
                            <p><?php echo isset($text['feature4_desc']) ? $text['feature4_desc'] : 'A dedicated space featuring sponsored courses and hackathons to advance your skills and career.'; ?></p>
                        </div>
                    </div>
                    
                    <div class="aboutus-feature-item">
                        <div class="aboutus-feature-number">05</div>
                        <div class="aboutus-feature-content">
                            <h4><?php echo isset($text['feature5_title']) ? $text['feature5_title'] : 'Specialization-based competitions'; ?></h4>
                            <p><?php echo isset($text['feature5_desc']) ? $text['feature5_desc'] : 'Engaging challenges to expand knowledge beyond specialization'; ?></p>
                        </div>
                    </div>
                    
                    <div class="aboutus-feature-item">
                        <div class="aboutus-feature-number">06</div>
                        <div class="aboutus-feature-content">
                            <h4><?php echo isset($text['feature6_title']) ? $text['feature6_title'] : 'Smart study schedule'; ?></h4>
                            <p><?php echo isset($text['feature6_desc']) ? $text['feature6_desc'] : 'Intelligent time management optimized for exam preparation'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- What We Offer Section -->
        <section class="aboutus-section aboutus-features-section">
            <div class="aboutus-container">
                <h2 class="aboutus-section-title">
                    <?php echo isset($text['offer_title']) ? $text['offer_title'] : 'What Do We Offer Differently?'; ?>
                </h2>
                <p class="aboutus-section-subtitle">
                    <?php echo isset($text['offer_subtitle']) ? $text['offer_subtitle'] : 'Discover the unique features that set IGM apart from traditional learning platforms'; ?>
                </p>
                
                <div class="aboutus-cards-grid">
                    <!-- البطاقات الست (مع ترجمة) -->
                    <div class="aboutus-card aboutus-feature-card">
                        <div class="aboutus-card-icon"><i class="fas fa-brain"></i></div>
                        <h3><?php echo isset($text['offer1_title']) ? $text['offer1_title'] : 'Learning Style Discovery'; ?></h3>
                        <p><?php echo isset($text['offer1_desc']) ? $text['offer1_desc'] : 'Through questions designed to discover each student\'s unique study style.'; ?></p>
                    </div>
                    <div class="aboutus-card aboutus-feature-card">
                        <div class="aboutus-card-icon"><i class="fas fa-pen-to-square"></i></div>
                        <h3><?php echo isset($text['offer2_title']) ? $text['offer2_title'] : 'Smart Tests'; ?></h3>
                        <p><?php echo isset($text['offer2_desc']) ? $text['offer2_desc'] : 'The student attaches the material, and we generate questions tailored to their learning style.'; ?></p>
                    </div>
                    <div class="aboutus-card aboutus-feature-card">
                        <div class="aboutus-card-icon"><i class="fas fa-chart-line"></i></div>
                        <h3><?php echo isset($text['offer3_title']) ? $text['offer3_title'] : 'Detailed Performance Analysis'; ?></h3>
                        <p><?php echo isset($text['offer3_desc']) ? $text['offer3_desc'] : 'We display the time spent, time spent on each question, and errors with their solutions.'; ?></p>
                    </div>
                    <div class="aboutus-card aboutus-feature-card">
                        <div class="aboutus-card-icon"><i class="fas fa-coffee"></i></div>
                        <h3><?php echo isset($text['offer4_title']) ? $text['offer4_title'] : 'Courses & Hackathons Newsletters'; ?></h3>
                        <p><?php echo isset($text['offer4_desc']) ? $text['offer4_desc'] : 'A constantly updated stream of advertised courses and hackathons relevant to your academic and professional aspirations.'; ?></p>
                    </div>
                    <div class="aboutus-card aboutus-feature-card">
                        <div class="aboutus-card-icon"><i class="fas fa-trophy"></i></div>
                        <h3><?php echo isset($text['offer5_title']) ? $text['offer5_title'] : 'Specialization-Based Competitions'; ?></h3>
                        <p><?php echo isset($text['offer5_desc']) ? $text['offer5_desc'] : 'With the possibility of undertaking experiments outside the core specialization to expand knowledge.'; ?></p>
                    </div>
                    <div class="aboutus-card aboutus-feature-card">
                        <div class="aboutus-card-icon"><i class="fas fa-calendar-check"></i></div>
                        <h3><?php echo isset($text['offer6_title']) ? $text['offer6_title'] : 'Smart Study Schedule'; ?></h3>
                        <p><?php echo isset($text['offer6_desc']) ? $text['offer6_desc'] : 'Automatically divides classes according to the days remaining before the exam, at a rate of 3-4 hours per day.'; ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Team Section -->
        <section class="aboutus-section aboutus-team-section">
            <div class="aboutus-container">
                <h2 class="aboutus-section-title">
                    <?php echo isset($text['team_title']) ? $text['team_title'] : 'Our Team'; ?>
                </h2>
                <p class="aboutus-section-subtitle">
                    <?php echo isset($text['team_subtitle']) ? $text['team_subtitle'] : 'Five talented developers from King Faisal University'; ?>
                </p>
                
                <div class="aboutus-team-grid">
                    <!-- أسماء الفريق (أسماء علم لا تترجم) -->
                    <div class="aboutus-team-member">
                        <div class="aboutus-team-avatar"><i class="fas fa-user-circle"></i></div>
                        <h3>Bashayer Jawad Alnajjar</h3>
                        <p class="aboutus-team-role">Information Systems Major</p>
                        <p class="aboutus-team-bio">Creative developer focused on innovative solutions</p>
                    </div>
                    <div class="aboutus-team-member">
                        <div class="aboutus-team-avatar"><i class="fas fa-user-circle"></i></div>
                        <h3>Amnah Ahmad Alshuhayb</h3>
                        <p class="aboutus-team-role">Information Systems Major</p>
                        <p class="aboutus-team-bio">Passionate about user experience design</p>
                    </div>
                    <div class="aboutus-team-member">
                        <div class="aboutus-team-avatar"><i class="fas fa-user-circle"></i></div>
                        <h3>Norah Amin Almajed</h3>
                        <p class="aboutus-team-role">Information Systems Major</p>
                        <p class="aboutus-team-bio">Expert in backend development and databases</p>
                    </div>
                    <div class="aboutus-team-member">
                        <div class="aboutus-team-avatar"><i class="fas fa-user-circle"></i></div>
                        <h3>Doaa Abdulrahman Masaad</h3>
                        <p class="aboutus-team-role">Information Systems Major</p>
                        <p class="aboutus-team-bio">Specialist in AI and machine learning integration</p>
                    </div>
                    <div class="aboutus-team-member">
                        <div class="aboutus-team-avatar"><i class="fas fa-user-circle"></i></div>
                        <h3>Fatimah Abdullah Alobaid</h3>
                        <p class="aboutus-team-role">Information Systems Major</p>
                        <p class="aboutus-team-bio">Dedicated to project management and coordination</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Thanks Section -->
        <section class="aboutus-section">
            <div class="aboutus-container">
                <h2 class="aboutus-section-title">
                    <?php echo isset($text['thanks_title']) ? $text['thanks_title'] : 'Special Thanks'; ?>
                </h2>
                <div class="aboutus-english-text">
                    <p>
                        <?php echo isset($text['thanks_text']) ? $text['thanks_text'] : 'We thank Dr. Yonis Gulzar and Dr. Shaymaa E.Sorour for their continuous support and valuable guidance that helped us turn our idea into a tangible reality.'; ?>
                    </p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <?php include 'footer.php'; ?>
    </div>

    <script>
        // نفس السكربتات السابقة
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
    </script>
</body>
</html>