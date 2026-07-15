<?php
// sidebar.php
?>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="nav-menu">
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active' : ''; ?>">
                <a href="home.php<?php echo isset($_GET['lang']) ? '?lang='.$_GET['lang'] : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'questionlevel.php' ? 'active' : ''; ?>">
                <a href="questionlevel.php<?php echo isset($_GET['lang']) ? '?lang='.$_GET['lang'] : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Study Behavior Questions</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'upload_file.php' ? 'active' : ''; ?>">
                <a href="upload_file.php<?php echo isset($_GET['lang']) ? '?lang='.$_GET['lang'] : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Start Learning</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'timing_schedule.php' ? 'active' : ''; ?>">
                <a href="timing_schedule.php<?php echo isset($_GET['lang']) ? '?lang='.$_GET['lang'] : ''; ?>">
                    <i class="fas fa-trophy"></i>
                    <span>Timing Schedule</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'hakathons.php' ? 'active' : ''; ?>">
                <a href="hakathons.php<?php echo isset($_GET['lang']) ? '?lang='.$_GET['lang'] : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Courses & Hackathons</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'aboutus.php' ? 'active' : ''; ?>">
                <a href="aboutus.php<?php echo isset($_GET['lang']) ? '?lang='.$_GET['lang'] : ''; ?>">
                    <i class="fas fa-info-circle"></i>
                    <span>About Us</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'contactus.php' ? 'active' : ''; ?>">
                <a href="contactus.php<?php echo isset($_GET['lang']) ? '?lang='.$_GET['lang'] : ''; ?>">
                    <i class="fas fa-headset"></i>
                    <span>Contact Us</span>
                </a>
            </li>
        </ul>
    </div>