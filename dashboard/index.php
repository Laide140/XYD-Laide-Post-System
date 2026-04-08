<?php
require_once('../config.php');
$conn = new mysqli($server_hostname, $server_username, $server_password, $server_database);
$userimg = "../unkown.webp";

// 处理添加用户
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_user') {
        $new_username = trim($_POST['username']);
        $new_password = trim($_POST['password']);
        $new_post = trim($_POST['post']);
        $new_mainpost = trim($_POST['mainpost']);
        $new_postadmin = isset($_POST['postadmin']) ? 1 : 0;
        
        // 简单验证
        if (!empty($new_username) && !empty($new_password) && !empty($new_post)) {
            // 检查用户名是否已存在
            $check_sql = "SELECT id FROM users WHERE username = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $new_username);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                // 使用MD5加密密码
                $encrypted_password = md5($new_password);
                $insert_sql = "INSERT INTO users (username, password, post, mainpost, postadmin, create_time, update_time) VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("ssssi", $new_username, $encrypted_password, $new_post, $new_mainpost, $new_postadmin);
                $insert_stmt->execute();
                $success_msg = "用户添加成功！";
            } else {
                $error_msg = "用户名已存在！";
            }
            $check_stmt->close();
        } else {
            $error_msg = "用户名、密码和邮政机构不能为空！";
        }
    } elseif ($_POST['action'] === 'delete_user') {
        $user_id = intval($_POST['user_id']);
        // 防止删除自己
        if (isset($_COOKIE["username"])) {
            $current_user_sql = "SELECT id FROM users WHERE username = ?";
            $current_stmt = $conn->prepare($current_user_sql);
            $current_stmt->bind_param("s", $_COOKIE["username"]);
            $current_stmt->execute();
            $current_result = $current_stmt->get_result();
            $current_user = $current_result->fetch_assoc();
            
            if ($current_user && $current_user['id'] != $user_id) {
                $delete_sql = "DELETE FROM users WHERE id = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("i", $user_id);
                $delete_stmt->execute();
                $success_msg = "用户删除成功！";
                $delete_stmt->close();
            } else {
                $error_msg = "不能删除自己的账号！";
            }
            $current_stmt->close();
        } else {
            $error_msg = "请先登录！";
        }
    }
    
    // 刷新页面以显示更新后的数据
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_COOKIE["username"])) {
    $username = $_COOKIE["username"];
    $userlogin = true;
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $_COOKIE["username"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    if ($user_data) {
        $userct = $user_data['create_time'];
        $userut = $user_data['update_time'];
        $userrole = $user_data['role'];
        $userpost = $user_data['post'];
        $postadmin = $user_data['postadmin'];
        $enableQPT = $user_data['enableQPT'];
        $enableOS = $user_data['enableOS'];
    } else {
        $userct = $userut = $userrole = $userpost = $postadmin = $enableQPT = $enableOS = "";
    }
    $sql = "SELECT COUNT(*) as total FROM users";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $users_count = $row['total'];
    $sql = "SELECT COUNT(*) as total FROM users WHERE postadmin=1;";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $yg_count = $row['total'];
} else {
    $userpost = "";
    $username = "未登录";
    $userlogin = false;
}

if($userrole != "admin")
{
    http_response_code(404);
    exit;
}

$sql = "SELECT * FROM users ORDER BY id ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
  <!--begin::Head-->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>新一代营业渠道系统 | 仪表盘</title>

    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
    <!--end::Accessibility Meta Tags-->

    <!--begin::Primary Meta Tags-->
    <meta name="title" content="新一代营业渠道系统 | 仪表盘" />
    <meta name="author" content="Laide" />
    <meta
      name="description"
      content="新一代营业渠道系统后台仪表盘"
    />
    <!--end::Primary Meta Tags-->

    <!--begin::Accessibility Features-->
    <meta name="supported-color-schemes" content="light dark" />
    <link rel="preload" href="./css/adminlte.css" as="style" />
    <!--end::Accessibility Features-->

    <!--begin::Fonts-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
      integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q="
      crossorigin="anonymous"
      media="print"
      onload="this.media = 'all'"
    />
    <!--end::Fonts-->

    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(OverlayScrollbars)-->

    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(Bootstrap Icons)-->

    <!--begin::Required Plugin(AdminLTE)-->
    <link rel="stylesheet" href="./css/adminlte.css" />
    <!--end::Required Plugin(AdminLTE)-->

    <!-- apexcharts -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css"
      integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0="
      crossorigin="anonymous"
    />

    <!-- jsvectormap -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css"
      integrity="sha256-+uGLJmmTKOqBr+2E6KDYs/NRsHxSkONXFHUL0fy2O/4="
      crossorigin="anonymous"
    />
    <style>
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-delete:hover {
            background-color: #c82333;
        }
        .btn-add {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-add:hover {
            background-color: #218838;
        }
        .add-button-container {
            margin: 20px 0;
            text-align: right;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .modal-header {
            padding: 15px 20px;
            background-color: #007bff;
            color: white;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 18px;
        }
        .modal-header .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        .modal-header .close:hover {
            opacity: 0.7;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-radius: 0 0 8px 8px;
            text-align: right;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        .form-group input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }
        .btn-submit {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-submit:hover {
            background-color: #0069d9;
        }
        .btn-cancel {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }
        .btn-cancel:hover {
            background-color: #5a6268;
        }
    </style>
  </head>
  <!--end::Head-->
  <!--begin::Body-->
  <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
      <!--begin::Header-->
      <nav class="app-header navbar navbar-expand bg-body">
        <!--begin::Container-->
        <div class="container-fluid">
          <!--begin::Start Navbar Links-->
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                <i class="bi bi-list"></i>
              </a>
            </li>
          </ul>
          <!--end::Start Navbar Links-->

          <!--begin::End Navbar Links-->
          <ul class="navbar-nav ms-auto">
            <!--begin::Navbar Search-->
            <li class="nav-item">
              <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                <i class="bi bi-search"></i>
              </a>
            </li>
            <!--end::Navbar Search-->

            <!--begin::Fullscreen Toggle-->
            <li class="nav-item">
              <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
              </a>
            </li>
            <!--end::Fullscreen Toggle-->

            <!--begin::User Menu Dropdown-->
            <li class="nav-item dropdown user-menu">
              <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <img
                  src="<?php echo $userimg; ?>"
                  class="user-image rounded-circle shadow"
                  alt="User Image"
                />
                <span class="d-none d-md-inline"><?php echo $username ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <!--begin::User Image-->
                <li class="user-header text-bg-primary">
                  <img
                    src="<?php echo $userimg ?>"
                    class="rounded-circle shadow"
                    alt="User Image"
                  />
                  <p>
                    <?php echo $username ?>
                  </p>
                </li>
                <!--end::User Image-->
              </ul>
            </li>
            <!--end::User Menu Dropdown-->
          </ul>
          <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
      </nav>
      <!--end::Header-->

      <!--begin::App Main-->
      <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
          <!--begin::Container-->
          <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
              <div class="col-sm-6">
                <h3 class="mb-0">Dashboard</h3>
              </div>
              <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                  <li class="breadcrumb-item"><a href="#">Home</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                </ol>
              </div>
            </div>
            <!--end::Row-->
          </div>
          <!--end::Container-->
        </div>
        <!--end::App Content Header-->
        <!--begin::App Content-->
        <div class="app-content">
          <!--begin::Container-->
          <div class="container-fluid">
            <!-- 显示消息 -->
            <?php if (isset($success_msg)): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            
            <!--begin::Row-->
            <div class="row">
              <div class="col-lg-3 col-6">
                <!--begin::Small Box Widget 3-->
                <div class="small-box text-bg-warning">
                  <div class="inner">
                    <h3><?php echo $users_count ?></h3>
                    <p>用户数量</p>
                  </div>
                  <svg
                    class="small-box-icon"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                  >
                    <path
                      d="M6.25 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM3.25 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM19.75 7.5a.75.75 0 00-1.5"
                    ></path>
                  </svg>
                </div>
              </div>
              <div class="col-lg-3 col-6">
                <!--begin::Small Box Widget 3-->
                <div class="small-box text-bg-warning">
                  <div class="inner">
                    <h3><?php echo $yg_count ?></h3>
                    <p>邮政管理员数量</p>
                  </div>
                  <svg
                    class="small-box-icon"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                  >
                    <path
                      d="M6.25 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM3.25 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM19.75 7.5a.75.75 0 00-1.5"
                    ></path>
                  </svg>
                </div>
                <!--end::Small Box Widget 3-->
              </div>
              <!--end::Col-->
            </div>
            <!--end::Row-->
          </div>
          <!--end::Container-->
          
          <div class="card">
              <div class="card-header">
                  <h3 class="card-title">用户列表</h3>
              </div>
              <div class="card-body p-0">
                 <table class="table table-striped">
                    <thead>
                          <tr>
                            <th style="width: 10px">ID</th>
                            <th>用户名</th>
                            <th>邮政机构</th>
                            <th>上级邮政机构</th>
                            <th>邮政管理员</th>
                            <th>操作</th>
                          </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($u['id']); ?></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo htmlspecialchars($u['post']); ?></td>
                            <td><?php echo htmlspecialchars($u['mainpost']); ?></td>
                            <td><?php if($u['postadmin']) echo "是"; else echo "否"; ?></td>
                            <td>
                                <?php if($u['username'] != $username): ?>
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('确定要删除用户 <?php echo htmlspecialchars($u['username']); ?> 吗？');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" class="btn-delete">删除</button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted">当前用户</span>
                                <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                    </tbody>
                  </table>
              </div>
              <div class="card-footer">
                  <div class="add-button-container">
                      <button type="button" class="btn-add" onclick="openAddUserModal()">
                          <i class="bi bi-plus-lg"></i> 添加用户
                      </button>
                  </div>
              </div>
          </div>
        </div>
        
        <!-- 添加用户模态窗口 -->
        <div id="addUserModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>创建新用户</h3>
                    <span class="close" onclick="closeAddUserModal()">&times;</span>
                </div>
                <form method="POST" action="" id="addUserForm">
                    <input type="hidden" name="action" value="add_user">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="username">用户名 <span style="color: red;">*</span></label>
                            <input type="text" name="username" id="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">密码 <span style="color: red;">*</span></label>
                            <input type="password" name="password" id="password" required>
                        </div>
                        <div class="form-group">
                            <label for="post">邮政机构 <span style="color: red;">*</span></label>
                            <input type="text" name="post" id="post" required>
                        </div>
                        <div class="form-group">
                            <label for="mainpost">上级邮政机构</label>
                            <input type="text" name="mainpost" id="mainpost">
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="postadmin" value="1"> 邮政管理员
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" onclick="closeAddUserModal()">取消</button>
                        <button type="submit" class="btn-submit">创建用户</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!--end::App Content-->
      </main>
      <!--end::App Main-->
      
      <!--begin::Footer-->
      <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">Anything you want</div>
        <strong>
          Copyright &copy; 2014-2026&nbsp;
          <a href="https://adminlte.io" class="text-decoration-none">AdminLTE.io</a>.
        </strong>
        All rights reserved.
      </footer>
      <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->
    
    <script>
        // 打开添加用户模态窗口
        function openAddUserModal() {
            document.getElementById('addUserModal').style.display = 'block';
            // 重置表单
            document.getElementById('addUserForm').reset();
        }
        
        // 关闭添加用户模态窗口
        function closeAddUserModal() {
            document.getElementById('addUserModal').style.display = 'none';
        }
        
        // 点击模态窗口外部关闭
        window.onclick = function(event) {
            var modal = document.getElementById('addUserModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    
    <!--begin::Script-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <script
      src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
    <script
      src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->
    <script src="./js/adminlte.js"></script>
    <!--end::Required Plugin(AdminLTE)--><!--begin::OverlayScrollbars Configure-->
    <script>
      const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
      const Default = {
        scrollbarTheme: 'os-theme-light',
        scrollbarAutoHide: 'leave',
        scrollbarClickScroll: true,
      };
      document.addEventListener('DOMContentLoaded', function () {
        const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
        const isMobile = window.innerWidth <= 992;
        if (
          sidebarWrapper &&
          OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined &&
          !isMobile
        ) {
          OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
            scrollbars: {
              theme: Default.scrollbarTheme,
              autoHide: Default.scrollbarAutoHide,
              clickScroll: Default.scrollbarClickScroll,
            },
          });
        }
      });
    </script>
    <!--end::OverlayScrollbars Configure-->

    <!-- OPTIONAL SCRIPTS -->
    <!-- sortablejs -->
    <script
      src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"
      crossorigin="anonymous"
    ></script>
    <script>
      new Sortable(document.querySelector('.connectedSortable'), {
        group: 'shared',
        handle: '.card-header',
      });
      const cardHeaders = document.querySelectorAll('.connectedSortable .card-header');
      cardHeaders.forEach((cardHeader) => {
        cardHeader.style.cursor = 'move';
      });
    </script>

    <!-- apexcharts -->
    <script
      src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
      integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8="
      crossorigin="anonymous"
    ></script>
    <script>
      const sales_chart_options = {
        series: [
          {
            name: 'Digital Goods',
            data: [28, 48, 40, 19, 86, 27, 90],
          },
          {
            name: 'Electronics',
            data: [65, 59, 80, 81, 56, 55, 40],
          },
        ],
        chart: {
          height: 300,
          type: 'area',
          toolbar: {
            show: false,
          },
        },
        legend: {
          show: false,
        },
        colors: ['#0d6efd', '#20c997'],
        dataLabels: {
          enabled: false,
        },
        stroke: {
          curve: 'smooth',
        },
        xaxis: {
          type: 'datetime',
          categories: [
            '2023-01-01',
            '2023-02-01',
            '2023-03-01',
            '2023-04-01',
            '2023-05-01',
            '2023-06-01',
            '2023-07-01',
          ],
        },
        tooltip: {
          x: {
            format: 'MMMM yyyy',
          },
        },
      };
      const sales_chart = new ApexCharts(
        document.querySelector('#revenue-chart'),
        sales_chart_options,
      );
      sales_chart.render();
    </script>

    <!-- jsvectormap -->
    <script
      src="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/js/jsvectormap.min.js"
      integrity="sha256-/t1nN2956BT869E6H4V1dnt0X5pAQHPytli+1nTZm2Y="
      crossorigin="anonymous"
    ></script>
    <script
      src="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/maps/world.js"
      integrity="sha256-XPpPaZlU8S/HWf7FZLAncLg2SAkP8ScUTII89x9D3lY="
      crossorigin="anonymous"
    ></script>
    <script>
      new jsVectorMap({
        selector: '#world-map',
        map: 'world',
      });
      const option_sparkline1 = {
        series: [{ data: [1000, 1200, 920, 927, 931, 1027, 819, 930, 1021] }],
        chart: { type: 'area', height: 50, sparkline: { enabled: true } },
        stroke: { curve: 'straight' },
        fill: { opacity: 0.3 },
        yaxis: { min: 0 },
        colors: ['#DCE6EC'],
      };
      const sparkline1 = new ApexCharts(document.querySelector('#sparkline-1'), option_sparkline1);
      sparkline1.render();
      const option_sparkline2 = {
        series: [{ data: [515, 519, 520, 522, 652, 810, 370, 627, 319, 630, 921] }],
        chart: { type: 'area', height: 50, sparkline: { enabled: true } },
        stroke: { curve: 'straight' },
        fill: { opacity: 0.3 },
        yaxis: { min: 0 },
        colors: ['#DCE6EC'],
      };
      const sparkline2 = new ApexCharts(document.querySelector('#sparkline-2'), option_sparkline2);
      sparkline2.render();
      const option_sparkline3 = {
        series: [{ data: [15, 19, 20, 22, 33, 27, 31, 27, 19, 30, 21] }],
        chart: { type: 'area', height: 50, sparkline: { enabled: true } },
        stroke: { curve: 'straight' },
        fill: { opacity: 0.3 },
        yaxis: { min: 0 },
        colors: ['#DCE6EC'],
      };
      const sparkline3 = new ApexCharts(document.querySelector('#sparkline-3'), option_sparkline3);
      sparkline3.render();
    </script>
    <!--end::Script-->
  </body>
  <!--end::Body-->
</html>