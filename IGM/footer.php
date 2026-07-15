<?php
// footer.php
?>
    <footer class="footer-main">
        <div class="footer-content">
            <div class="footer-container">
                <div class="footer-top">
                    <div class="footer-column">
                        <div class="footer-logo">
                            <img src="img/logo.png" alt="IGM Logo" class="footer-logo-img">
                            <span class="footer-brand">IGM</span>
                        </div>
                        <p class="footer-tagline"><?php echo isset($text['footer_tagline']) ? $text['footer_tagline'] : 'Learn Smarter... Not Harder'; ?></p>
                        <p class="footer-description"><?php echo isset($text['footer_desc']) ? $text['footer_desc'] : 'Innovative learning platform for students'; ?></p>
                    </div>
                </div>
                <div class="footer-bottom">
                    <div class="footer-bottom-left">
                        &copy; <?php echo date('Y'); ?> IGM. <?php echo isset($text['all_rights']) ? $text['all_rights'] : 'All rights reserved.'; ?>
                    </div>
                </div>
            </div>
        </div>
    </footer>