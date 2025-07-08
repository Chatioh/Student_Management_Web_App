<?php
session_start();
require_once "config.php";

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../login.php");
    exit;
}

$student_id = $_SESSION["student_id"];

// Get student data for profile icon
$student_data = [];
$sql = "SELECT full_name FROM students WHERE id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $student_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Handle mark as read action
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND student_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $notification_id, $student_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Handle mark all as read action
if (isset($_POST['mark_all_read'])) {
    $sql = "UPDATE notifications SET is_read = 1 WHERE student_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Get notifications for the logged-in student
$notifications = [];
$sql = "SELECT id, title, message, type, is_read, created_at 
        FROM notifications 
        WHERE student_id = ? 
        ORDER BY created_at DESC";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Get notification statistics
$unread_count = count(array_filter($notifications, function($n) { return !$n['is_read']; }));
$total_count = count($notifications);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Student Management Dashboard</title>
    <link href="assets/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/libs/fontawesome-free-6.7.2-web/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/courses.css">
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="logo">SMA</div>
        
        <div class="nav-section">
            <a href="home.php" class="nav-link">
                <i class="fas fa-home"></i>
                Home
            </a>
            <a href="courses.php" class="nav-link">
                <i class="fas fa-th-large"></i>
                Courses 
                <i class="fas fa-chevron-right ms-auto"></i>
            </a>
            <a href="examresults.php" class="nav-link">
                <i class="fas fa-th-large"></i>
                Exam Results 
                <i class="fas fa-chevron-right ms-auto"></i>
            </a>
            <a href="notifications.php" class="nav-link active">
                <i class="fas fa-bell"></i>
                Notifications 
                <i class="fas fa-chevron-right ms-auto"></i>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Account</div>
            <a href="#" class="nav-link">
                <i class="fas fa-cog"></i>
                Settings
                <i class="fas fa-chevron-right ms-auto"></i>
            </a>
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user-alt"></i>
                Profile
                <i class="fas fa-chevron-right ms-auto"></i>
            </a>
            <a href="#" class="nav-link" onclick="showLogoutModal()">
                <i class="fas fa-sign-out-alt"></i>
                Logout
                <i class="fas fa-chevron-right ms-auto"></i>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content px-0" id="mainContent">
        <div class="container-fluid">
          <div class="container">
              <!-- Top Bar -->
              <div class="top-bar">
                  <div class="d-flex align-items-center flex-grow-1">
                      <button class="mobile-toggle me-3" id="mobileToggle">
                          <i class="fas fa-bars"></i>
                      </button>
                      <div class="search-box">
                          <i class="fas fa-search"></i>
                          <input type="text" class="form-control" placeholder="Search...">
                      </div>
                      <div class="user-avatar ms-2"><?php echo strtoupper(substr($student_data["full_name"], 0, 1)); ?></div>
                  </div>
              </div>

              <!-- Notifications Header -->
              <div class="row mb-4">
                  <div class="col-12">
                      <div class="d-flex justify-content-between align-items-center">
                          <div>
                              <h4 class="mb-1">Notifications</h4>
                              <p class="text-muted mb-0">You have <?php echo $unread_count; ?> unread notifications</p>
                          </div>
                          <?php if ($unread_count > 0): ?>
                              <form method="POST" style="display: inline;">
                                  <button type="submit" name="mark_all_read" class="btn btn-outline-primary">
                                      <i class="fas fa-check-double"></i> Mark All as Read
                                  </button>
                              </form>
                          <?php endif; ?>
                      </div>
                  </div>
              </div>

              <!-- Notifications List -->
              <div class="row">
                  <div class="col-12">
                      <div class="chart-container">
                          <?php if (!empty($notifications)): ?>
                              <div class="notification-list">
                                  <?php foreach ($notifications as $notification): ?>
                                      <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?> mb-3 p-3 border rounded">
                                          <div class="d-flex justify-content-between align-items-start">
                                              <div class="d-flex align-items-start flex-grow-1">
                                                  <div class="notification-icon me-3">
                                                      <?php
                                                      $icon_class = 'fas fa-info-circle text-info';
                                                      switch($notification['type']) {
                                                          case 'warning':
                                                              $icon_class = 'fas fa-exclamation-triangle text-warning';
                                                              break;
                                                          case 'success':
                                                              $icon_class = 'fas fa-check-circle text-success';
                                                              break;
                                                          case 'danger':
                                                              $icon_class = 'fas fa-times-circle text-danger';
                                                              break;
                                                      }
                                                      ?>
                                                      <i class="<?php echo $icon_class; ?>"></i>
                                                  </div>
                                                  <div class="flex-grow-1">
                                                      <h6 class="mb-1 <?php echo !$notification['is_read'] ? 'fw-bold' : ''; ?>">
                                                          <?php echo htmlspecialchars($notification['title']); ?>
                                                          <?php if (!$notification['is_read']): ?>
                                                              <span class="badge bg-primary ms-2">New</span>
                                                          <?php endif; ?>
                                                      </h6>
                                                      <p class="mb-2 text-muted">
                                                          <?php echo htmlspecialchars($notification['message']); ?>
                                                      </p>
                                                      <small class="text-muted">
                                                          <i class="fas fa-clock"></i>
                                                          <?php echo date('M j, Y \a\t g:i A', strtotime($notification['created_at'])); ?>
                                                      </small>
                                                  </div>
                                              </div>
                                              <?php if (!$notification['is_read']): ?>
                                                  <form method="POST" style="display: inline;">
                                                      <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                      <button type="submit" name="mark_read" class="btn btn-sm btn-outline-secondary">
                                                          <i class="fas fa-check"></i>
                                                      </button>
                                                  </form>
                                              <?php endif; ?>
                                          </div>
                                      </div>
                                  <?php endforeach; ?>
                              </div>
                          <?php else: ?>
                              <div class="text-center py-5">
                                  <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                                  <h5 class="text-muted">No notifications</h5>
                                  <p class="text-muted">You're all caught up! No new notifications at this time.</p>
                              </div>
                          <?php endif; ?>
                      </div>
                  </div>
              </div>

              <!-- Notification Statistics -->
              <div class="row mt-4">
                  <div class="col-md-4">
                      <div class="stat-card text-center">
                          <div class="stat-icon mx-auto mb-2" style="background-color: rgba(108, 92, 231, 0.1); color: var(--primary-color);">
                              <i class="fas fa-bell"></i>
                          </div>
                          <div class="stat-value"><?php echo $total_count; ?></div>
                          <div class="stat-label">Total Notifications</div>
                      </div>
                  </div>
                  <div class="col-md-4">
                      <div class="stat-card text-center">
                          <div class="stat-icon mx-auto mb-2" style="background-color: rgba(231, 76, 60, 0.1); color: var(--danger-color);">
                              <i class="fas fa-envelope"></i>
                          </div>
                          <div class="stat-value"><?php echo $unread_count; ?></div>
                          <div class="stat-label">Unread</div>
                      </div>
                  </div>
                  <div class="col-md-4">
                      <div class="stat-card text-center">
                          <div class="stat-icon mx-auto mb-2" style="background-color: rgba(0, 184, 148, 0.1); color: var(--success-color);">
                              <i class="fas fa-envelope-open"></i>
                          </div>
                          <div class="stat-value"><?php echo $total_count - $unread_count; ?></div>
                          <div class="stat-label">Read</div>
                      </div>
                  </div>
              </div>
          </div>
        </div>
    </main>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to logout?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile menu toggle
        const mobileToggle = document.getElementById('mobileToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        });

        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });

        // Show logout modal
        function showLogoutModal() {
            const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
            logoutModal.show();
        }
    </script>

    <style>
        .notification-item {
            transition: all 0.3s ease;
            background-color: #fff;
        }
        
        .notification-item.unread {
            background-color: #f8f9ff;
            border-left: 4px solid #6c5ce7;
        }
        
        .notification-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #f8f9fa;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
    </style>
</body>
</html>

