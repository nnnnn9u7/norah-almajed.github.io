<?php
// test.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* تنسيقات بسيطة */
        .top-nav {
            background: #1a4b8c;
            color: white;
            padding: 12px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .language-switcher {
            margin-right: 10px;
        }
        .language-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            background: rgba(255,255,255,0.15);
            padding: 6px 12px;
            border-radius: 20px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .language-btn:hover {
            background: rgba(255,255,255,0.25);
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-avatar {
            width: 36px;
            height: 36px;
            background: #fcb408;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <div class="nav-left">IGM</div>
        <div class="user-section">
            <!-- زر الترجمة -->
            <div class="language-switcher">
                <a href="?lang=<?php echo isset($_GET['lang']) && $_GET['lang']=='ar' ? 'en' : 'ar'; ?>" class="language-btn">
                    <i class="fas fa-globe"></i>
                    <span class="language-text">
                        <?php echo isset($_GET['lang']) && $_GET['lang']=='ar' ? 'English' : 'العربية'; ?>
                    </span>
                </a>
            </div>
            <!-- صورة المستخدم -->
            <div class="user-info">
                <div class="user-avatar"><i class="fas fa-user"></i></div>
                <span>User Name</span>
            </div>
        </div>
    </div>
    <h1>Test Page</h1>
</body>
</html>