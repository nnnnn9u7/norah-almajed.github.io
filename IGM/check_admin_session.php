<?php
// check_admin_session.php
function checkAdminSession() {
    session_start();
    
    if (!isset($_SESSION['admin_id'])) {
        header("Location: admin_login.php");
        exit();
    }
    
    return $_SESSION;
}
?>