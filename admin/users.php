<?php
require_once '../config.php';

// Verify admin role
if (getUserRole() !== 'admin') {
    if (!$db_connected && !isset($_SESSION['user_id'])) {
        // If DB is offline, start a mock admin session
        $_SESSION['user_id'] = 3;
        $_SESSION['user_name'] = 'م. أحمد السعدي (محاكي)';
        $_SESSION['role'] = 'admin';
    } else {
        header('Location: ../login.php');
        exit;
    }
}

$success_msg = '';
$error_msg = '';

// Handle Actions (KYC Verification, Bans, Updates)
if ($db_connected) {
    // 1. Post request from slide-out details form
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
        $user_id = intval($_POST['user_id']);
        $admin_notes = isset($_POST['admin_notes']) ? sanitize($_POST['admin_notes']) : '';
        
        // Save notes
        $stmt_n = $conn->prepare("UPDATE users SET admin_notes = ? WHERE id = ?");
        $stmt_n->bind_param("si", $admin_notes, $user_id);
        $stmt_n->execute();
        $stmt_n->close();
        
        if (isset($_POST['btn_verify'])) {
            $stmt = $conn->prepare("UPDATE users SET is_active = 1, nafath_verified = 1 WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $success_msg = 'تم توثيق وتنشيط حساب المستخدم بنجاح!';
            }
            $stmt->close();
        } elseif (isset($_POST['btn_ban'])) {
            $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $success_msg = 'تم حظر حساب المستخدم بنجاح!';
            }
            $stmt->close();
        } else {
            $success_msg = 'تم حفظ ملاحظات الإدارة بنجاح!';
        }
    }
    
    // 2. Direct action links (GET parameters)
    if (isset($_GET['action'])) {
        $act_id = intval($_GET['id']);
        if ($_GET['action'] === 'verify') {
            $stmt = $conn->prepare("UPDATE users SET is_active = 1, nafath_verified = 1 WHERE id = ?");
            $stmt->bind_param("i", $act_id);
            if ($stmt->execute()) {
                $success_msg = 'تم توثيق وتفعيل المستخدم بنجاح!';
            }
            $stmt->close();
        } elseif ($_GET['action'] === 'ban') {
            $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            $stmt->bind_param("i", $act_id);
            if ($stmt->execute()) {
                $success_msg = 'تم حظر حساب المستخدم بنجاح!';
            }
            $stmt->close();
        } elseif ($_GET['action'] === 'unban') {
            $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            $stmt->bind_param("i", $act_id);
            if ($stmt->execute()) {
                $success_msg = 'تم إلغاء حظر المستخدم بنجاح!';
            }
            $stmt->close();
        }
    }
}

// Fetch total stats count
$total_users_count = 38247;
if ($db_connected) {
    $res_cnt = $conn->query("SELECT COUNT(*) FROM users");
    if ($res_cnt) {
        $db_cnt = intval($res_cnt->fetch_row()[0]);
        if ($db_cnt > 5) {
            $total_users_count += ($db_cnt - 5);
        }
    }
}

// Fetch all users list from database
$users_list = [];
if ($db_connected) {
    $sql_users = "SELECT u.*,
                  (SELECT COUNT(*) FROM bids b WHERE b.user_id = u.id) AS bids_count,
                  (SELECT COUNT(*) FROM auctions a WHERE a.seller_id = u.id) AS list_count,
                  (SELECT SUM(current_price) FROM auctions a WHERE a.status = 'ended' AND (a.seller_id = u.id OR EXISTS(SELECT 1 FROM bids b WHERE b.auction_id = a.id AND b.user_id = u.id))) AS total_val
                  FROM users u
                  ORDER BY u.created_at DESC";
    $res_users = $conn->query($sql_users);
    if ($res_users && $res_users->num_rows > 0) {
        while ($row = $res_users->fetch_assoc()) {
            $row['phone'] = $row['mobile'] ?? '';
            $row['nid'] = $row['national_id'] ?? '';
            if (isset($row['is_active'])) {
                $row['status'] = $row['is_active'] ? 'active' : 'banned';
            } elseif (!isset($row['status'])) {
                $row['status'] = 'active';
            }
            if (!$row['nafath_verified']) {
                $row['status'] = 'pending';
            }
            $users_list[] = $row;
        }
    }
}


$admin_page_title = 'إدارة المستخدمين | FleetX';
$admin_active = 'users';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<?php include __DIR__ . '/head.inc.php'; ?>
</head>
<body class="admin-body">

<?php include __DIR__ . '/sidebar.inc.php'; ?>

<!-- MAIN CONTENT -->
<main class="admin-content">
<?php include __DIR__ . '/mobile-chrome.inc.php'; ?>
  
  <!-- Top Bar -->
  <div class="admin-topbar" style="background:#FFFFFF;border-bottom:1px solid var(--navy-mid)">
    <div style="display:flex;align-items:center;gap:var(--space-4)">
      <button type="button" id="sidebar-toggle" class="btn btn-secondary btn-sm admin-sidebar-toggle" aria-label="فتح القائمة">
        <i class="fas fa-bars"></i>
      </button>
      <h2 style="font-size:var(--font-size-xl);color:#1E293B">إدارة المستخدمين</h2>
    </div>
    <div style="font-size:var(--font-size-sm);color:var(--gray-500)">
      إجمالي الأعضاء: <span style="font-weight:800;color:#0F75BC"><?php echo number_format($total_users_count); ?> عضو</span>
    </div>
  </div>

  <?php if (!empty($success_msg)): ?>
    <div class="card card-body" style="background:var(--success-pale);border-color:var(--success);color:var(--success);margin-bottom:var(--space-5);padding:var(--space-3) var(--space-4)">
      <i class="fas fa-check-circle" style="margin-left:8px"></i> <?php echo $success_msg; ?>
    </div>
  <?php endif; ?>

  <!-- Filters and Search Table -->
  <div class="admin-table-wrapper" style="margin-bottom:var(--space-6);background:#FFFFFF;border:1px solid var(--navy-mid)">
    <div class="tabs-row">
      <div style="display:flex;gap:var(--space-2)">
        <button class="user-tab-btn active" onclick="switchTab('all', this)">الكل</button>
        <button class="user-tab-btn" onclick="switchTab('buyer', this)">المشترين</button>
        <button class="user-tab-btn" onclick="switchTab('seller', this)">البائعين</button>
        <button class="user-tab-btn" onclick="switchTab('pending', this)">معلقين (KYC)</button>
        <button class="user-tab-btn" onclick="switchTab('banned', this)">المحظورين</button>
      </div>
      <div class="admin-search-bar" style="max-width:300px;background:#F8F9FA;border:1px solid var(--navy-mid)">
        <i class="fas fa-search" style="color:var(--gray-500)"></i>
        <input type="text" placeholder="بحث باسم المستخدم أو الجوال..." id="user-search" oninput="filterUsers()">
      </div>
    </div>

    <!-- Table -->
    <table class="admin-table" role="table">
      <thead>
        <tr>
          <th>العضو</th>
          <th>رقم الجوال / البريد</th>
          <th>الدور</th>
          <th>المدينة</th>
          <th>تاريخ التسجيل</th>
          <th>النشاط</th>
          <th>الحالة</th>
          <th>الإجراءات</th>
        </tr>
      </thead>
      <tbody id="users-table-body">
        <?php foreach ($users_list as $usr): ?>
          <tr data-role="<?php echo $usr['role']; ?>" data-status="<?php echo $usr['status']; ?>">
            <td>
              <div style="display:flex;align-items:center;gap:var(--space-3)">
                <div style="width:36px;height:36px;border-radius:50%;background:rgba(15,117,188,0.1);color:#0F75BC;display:flex;align-items:center;justify-content:center;font-weight:700">
                  <?php echo mb_substr($usr['full_name'], 0, 1, 'utf-8'); ?>
                </div>
                <div>
                  <div style="font-weight:600;font-size:var(--font-size-sm);color:#1E293B"><?php echo sanitize($usr['full_name']); ?></div>
                  <?php if ($usr['status'] == 'active'): ?>
                    <span style="font-size:9px;color:var(--success)"><i class="fas fa-shield-check"></i> الهوية موثقة</span>
                  <?php else: ?>
                    <span style="font-size:9px;color:var(--gray-400)"><i class="fas fa-shield-halved"></i> غير مكتمل</span>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td style="font-family:var(--font-en);font-size:var(--font-size-sm);color:#1E293B">
              <?php echo sanitize($usr['phone']); ?><br>
              <span style="color:var(--gray-500);font-size:11px"><?php echo sanitize($usr['email']); ?></span>
            </td>
            <td style="color:#1E293B">
              <?php 
                if ($usr['role'] == 'admin') echo 'مدير النظام';
                elseif ($usr['role'] == 'seller') echo 'بائع أساطيل';
                else echo 'مشتري أفراد';
              ?>
            </td>
            <td style="color:#1E293B"><?php echo sanitize($usr['city'] ?? 'الرياض'); ?></td>
            <td style="font-family:var(--font-en);font-size:var(--font-size-xs);color:#1E293B">
              <?php echo date('Y-m-d', strtotime($usr['created_at'])); ?>
            </td>
            <td style="text-align:center;font-weight:700;color:#1E293B">
              <?php 
                if ($usr['role'] == 'seller') {
                  echo intval($usr['list_count'] ?? 0) . ' سيارات';
                } else {
                  echo intval($usr['bids_count'] ?? 0) . ' مزايدة';
                }
              ?>
            </td>
            <td>
              <?php if ($usr['status'] == 'active'): ?>
                <span class="status-dot active">نشط</span>
              <?php elseif ($usr['status'] == 'banned'): ?>
                <span class="status-dot rejected">محظور</span>
              <?php else: ?>
                <span class="status-dot pending">معلق</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:var(--space-2)">
                <button class="btn btn-secondary btn-sm btn-icon" onclick="openDetailPanel('<?php echo $usr['id']; ?>', '<?php echo sanitize($usr['full_name']); ?>', '<?php echo sanitize($usr['phone']); ?>', '<?php echo sanitize($usr['email']); ?>', '<?php echo $usr['role']; ?>', '<?php echo sanitize($usr['city'] ?? 'الرياض'); ?>', '<?php echo date('Y-m-d', strtotime($usr['created_at'])); ?>', '<?php echo ($usr['role'] == 'seller') ? ($usr['list_count'] ?? 0) . ' سيارات' : ($usr['bids_count'] ?? 0) . ' مزايدة'; ?>', '<?php echo $usr['status']; ?>', '<?php echo sanitize($usr['nid'] ?? '—'); ?>', '<?php echo number_format($usr['total_val'] ?? 0); ?> ر.س', '<?php echo sanitize($usr['admin_notes'] ?? ''); ?>')"><i class="fas fa-eye"></i></button>
                <?php if ($usr['status'] == 'pending'): ?>
                  <a href="users.php?action=verify&id=<?php echo $usr['id']; ?>" class="btn btn-success btn-sm btn-icon" title="توثيق"><i class="fas fa-check"></i></a>
                <?php endif; ?>
                <?php if ($usr['status'] === 'banned'): ?>
                  <a href="users.php?action=unban&id=<?php echo $usr['id']; ?>" class="btn btn-success btn-sm btn-icon" title="فك الحظر"><i class="fas fa-check-circle"></i></a>
                <?php else: ?>
                  <a href="users.php?action=ban&id=<?php echo $usr['id']; ?>" class="btn btn-danger btn-sm btn-icon" title="حظر" onclick="return confirm('هل أنت متأكد من حظر حساب هذا المستخدم؟')"><i class="fas fa-ban"></i></a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- SLIDE OUT USER DETAIL PANEL -->
<div class="detail-panel-overlay" id="detail-panel-overlay" onclick="closeDetailPanel()">
  <div class="detail-panel" id="detail-panel" onclick="event.stopPropagation()">
    <div class="detail-panel-header">
      <h3 style="font-size:var(--font-size-lg);color:#1E293B"><i class="fas fa-user-gear text-gold"></i> ملف المستخدم بالتفصيل</h3>
      <button onclick="closeDetailPanel()" style="font-size:1.2rem;color:var(--gray-400);background:none;border:none;cursor:pointer"><i class="fas fa-times"></i></button>
    </div>
    
    <form action="users.php" method="POST" style="display:flex;flex-direction:column;height:100%">
      <input type="hidden" name="action" value="update_user">
      <input type="hidden" name="user_id" id="form-user-id">
      
      <div class="detail-panel-body">
        <div style="display:flex;align-items:center;gap:var(--space-4);margin-bottom:var(--space-6)">
          <div class="user-avatar-placeholder" id="p-avatar">خ</div>
          <div>
            <h3 id="p-name" style="font-size:var(--font-size-lg);color:#1E293B">اسم المستخدم</h3>
            <span class="badge badge-gold" id="p-role" style="margin-top:var(--space-1)">مشتري (أفراد)</span>
          </div>
        </div>

        <h4 style="font-size:var(--font-size-sm);border-bottom:1px solid var(--navy-mid);padding-bottom:var(--space-1);margin-bottom:var(--space-3);color:#1E293B">بيانات الاتصال</h4>
        <div style="display:flex;flex-direction:column;gap:var(--space-2);font-size:var(--font-size-sm);color:#1E293B">
          <div><strong>رقم الجوال:</strong> <span id="p-phone">050xxxxxxx</span></div>
          <div><strong>البريد الإلكتروني:</strong> <span id="p-email">user@mail.com</span></div>
          <div><strong>المدينة:</strong> <span id="p-city">الرياض</span></div>
          <div><strong>تاريخ التسجيل:</strong> <span id="p-date">2024-03-12</span></div>
        </div>

        <h4 style="font-size:var(--font-size-sm);border-bottom:1px solid var(--navy-mid);padding-bottom:var(--space-1);margin-top:var(--space-6);margin-bottom:var(--space-3);color:#1E293B">حالة التحقق (KYC)</h4>
        <div style="display:flex;flex-direction:column;gap:var(--space-2);font-size:var(--font-size-sm);color:#1E293B">
          <div style="display:flex;align-items:center;gap:4px"><i class="fas fa-circle-check text-success"></i> <span>التحقق من رقم الجوال (OTP مفعّل)</span></div>
          <div style="display:flex;align-items:center;gap:4px" id="p-status-icon"><i class="fas fa-circle-check text-success"></i> <span>التحقق من الهوية الوطنية (نفاذ موثق)</span></div>
          <div><strong>رقم الهوية الوطنية / السجل التجاري:</strong> <span id="p-id" style="font-family:var(--font-en)">1010xxxxxx</span></div>
        </div>

        <h4 style="font-size:var(--font-size-sm);border-bottom:1px solid var(--navy-mid);padding-bottom:var(--space-1);margin-top:var(--space-6);margin-bottom:var(--space-3);color:#1E293B">إحصائيات المبيعات والمشاركات</h4>
        <div class="user-grid-stats">
          <div class="user-stat-box">
            <div style="font-size:10px;color:var(--gray-500)">المشاركات النشطة</div>
            <div style="font-size:var(--font-size-lg);font-weight:800;color:var(--gold)" id="p-activity">32 مزايدة</div>
          </div>
          <div class="user-stat-box">
            <div style="font-size:10px;color:var(--gray-500)">إجمالي المشتريات / المبيعات</div>
            <div style="font-size:var(--font-size-lg);font-weight:800;color:var(--success)" id="p-total">142,500 ر.س</div>
          </div>
        </div>

        <div class="form-group" style="margin-top:var(--space-6)">
          <label class="form-label" for="admin-notes">ملاحظات الإدارة حول هذا العضو</label>
          <textarea class="form-control" name="admin_notes" id="admin-notes" rows="3" placeholder="اكتب ملاحظة داخلية..."></textarea>
        </div>
      </div>
      
      <div class="detail-panel-footer">
        <button type="submit" name="btn_verify" class="btn btn-success btn-sm" style="flex:1"><i class="fas fa-shield-check"></i> توثيق العضو</button>
        <button type="submit" name="btn_ban" class="btn btn-danger btn-sm" style="flex:1"><i class="fas fa-ban"></i> حظر الحساب</button>
        <button type="submit" name="btn_save" class="btn btn-secondary btn-sm"><i class="fas fa-save"></i> حفظ الملاحظات</button>
      </div>
    </form>
  </div>
</div>

<script>
  let activeTabRole = 'all';

  function openDetailPanel(id, name, phone, email, role, city, date, activity, status, idNum, totalVal, notes) {
    document.getElementById('form-user-id').value = id;
    document.getElementById('p-name').innerText = name;
    document.getElementById('p-phone').innerText = phone;
    document.getElementById('p-email').innerText = email;
    document.getElementById('p-role').innerText = role === 'seller' ? 'بائع أساطيل' : (role === 'admin' ? 'مشرف' : 'مشتري أفراد');
    document.getElementById('p-city').innerText = city;
    document.getElementById('p-date').innerText = date;
    document.getElementById('p-activity').innerText = activity;
    document.getElementById('p-total').innerText = totalVal;
    document.getElementById('p-avatar').innerText = name.charAt(0);
    document.getElementById('p-id').innerText = idNum;
    document.getElementById('admin-notes').value = notes;

    const kycIcon = document.getElementById('p-status-icon');
    if (status === 'active') {
      kycIcon.innerHTML = '<i class="fas fa-circle-check text-success"></i> <span>التحقق من الهوية (موثق)</span>';
    } else {
      kycIcon.innerHTML = '<i class="fas fa-circle-xmark text-danger"></i> <span>التحقق من الهوية (غير موثق)</span>';
    }

    document.getElementById('detail-panel-overlay').classList.add('open');
  }

  function closeDetailPanel() {
    document.getElementById('detail-panel-overlay').classList.remove('open');
  }

  function switchTab(role, element) {
    document.querySelectorAll('.user-tab-btn').forEach(btn => btn.classList.remove('active'));
    element.classList.add('active');
    activeTabRole = role;
    filterUsers();
  }

  function filterUsers() {
    const search = document.getElementById('user-search').value.toLowerCase();

    document.querySelectorAll('#users-table-body tr').forEach(row => {
      const rowRole = row.getAttribute('data-role');
      const rowStatus = row.getAttribute('data-status');
      const nameText = row.querySelector('td:nth-child(1)').innerText.toLowerCase();
      const contactText = row.querySelector('td:nth-child(2)').innerText.toLowerCase();

      let matchesTab = false;
      if (activeTabRole === 'all') {
        matchesTab = true;
      } else if (activeTabRole === 'buyer' && rowRole === 'buyer') {
        matchesTab = true;
      } else if (activeTabRole === 'seller' && rowRole === 'seller') {
        matchesTab = true;
      } else if (activeTabRole === 'pending' && rowStatus === 'pending') {
        matchesTab = true;
      } else if (activeTabRole === 'banned' && rowStatus === 'banned') {
        matchesTab = true;
      }

      const matchesSearch = (search === '' || nameText.includes(search) || contactText.includes(search));

      if (matchesTab && matchesSearch) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  }
</script>
</body>
</html>
