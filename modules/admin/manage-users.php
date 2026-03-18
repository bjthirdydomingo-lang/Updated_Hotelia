<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
$pdo = getDB();

$error_message = "";
$success_message = "";

// --- 1. HANDLE BULK ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $selected_ids = $_POST['user_ids'] ?? [];
    $action = $_POST['action_type'] ?? '';

    if (!empty($selected_ids) && in_array($action, ['active', 'inactive'])) {
        $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
        try {
            // Update the status for all selected account IDs
            $stmt = $pdo->prepare("UPDATE staff SET status = ? WHERE account_id IN ($placeholders)");
            $params = array_merge([$action], $selected_ids);
            if ($stmt->execute($params)) {
                header("Location: manage-users.php?bulk_success=1");
                exit;
            }
        } catch (PDOException $e) {
            $error_message = "Bulk Action Error: " . $e->getMessage();
        }
    } else {
        $error_message = "Please select users and a valid action.";
    }
}

// --- 2. HANDLE INDIVIDUAL ACCESS CONTROL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_access'])) {
    $account_id = $_POST['account_id'];
    $current_status = $_POST['current_status'];
    $new_status = ($current_status === 'active') ? 'inactive' : 'active'; 
    
    try {
        $stmt = $pdo->prepare("UPDATE staff SET status = ? WHERE account_id = ?");
        if ($stmt->execute([$new_status, $account_id])) {
            header("Location: manage-users.php?status_updated=1");
            exit;
        }
    } catch (PDOException $e) {
        $error_message = "Database Error: " . $e->getMessage();
    }
}

// --- 3. HANDLE CREATE STAFF ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_staff'])) {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } else {
        $pdo->beginTransaction();
        try {
            $stmtAcc = $pdo->prepare("INSERT INTO accounts (username, password, account_type) VALUES (?, ?, 'staff')");
            $stmtAcc->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            $account_id = $pdo->lastInsertId();

            $stmtStaff = $pdo->prepare("INSERT INTO staff (account_id, full_name, role, status) VALUES (?, ?, ?, 'active')");
            $stmtStaff->execute([$account_id, $full_name, $role]);

            $pdo->commit();
            header("Location: manage-users.php?success=1");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Fetch all staff accounts
$users = $pdo->query("
    SELECT a.account_id, a.username, a.account_type, s.full_name, s.role, s.status 
    FROM accounts a 
    JOIN staff s ON a.account_id = s.account_id 
    WHERE a.account_type != 'guest'
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users — Hotelia Admin</title>
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 flex font-mulish min-h-screen">
    <main class="flex-grow p-8">
        <div class="flex justify-between items-center mb-8">
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="p-2 hover:bg-gray-200 rounded-xl transition-colors text-gray-600 border border-transparent hover:border-gray-300">
                    <i data-lucide="arrow-left" class="w-6 h-6"></i>
                </a>
                <h2 class="text-3xl font-black text-teal-900 uppercase">Staff Accounts</h2>
            </div>
            <button onclick="openStaffModal()" class="px-6 py-3 bg-teal-600 text-white rounded-xl font-bold shadow-lg hover:bg-teal-700 transition-all">+ Create Staff Account</button>
        </div>

        <?php if (isset($_GET['bulk_success'])): ?><div class="mb-6 p-4 bg-teal-100 text-teal-700 rounded-xl font-bold">✅ Bulk action completed successfully!</div><?php endif; ?>
        <?php if (isset($_GET['status_updated'])): ?><div class="mb-6 p-4 bg-blue-100 text-blue-700 rounded-xl font-bold">🔄 Individual status updated!</div><?php endif; ?>
        <?php if ($error_message): ?><div class="mb-6 p-4 bg-rose-100 text-rose-700 rounded-xl font-bold">⚠️ <?php echo $error_message; ?></div><?php endif; ?>

        <form method="POST" id="bulkForm" onsubmit="return confirmBulkAction()">
            <div class="mb-4 flex items-center gap-4 bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                <span class="text-xs font-black uppercase text-gray-400">Bulk Actions:</span>
                <select name="action_type" id="actionType" class="p-2 border rounded-lg text-xs font-bold outline-none">
                    <option value="">Select Action...</option>
                    <option value="active">Activate Accounts</option>
                    <option value="inactive">Deactivate Accounts (Inactive)</option>
                </select>
                <button type="submit" name="bulk_action" class="px-4 py-2 bg-teal-600 text-white rounded-lg text-xs font-black uppercase tracking-widest hover:bg-black transition-all">Apply to Selected</button>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="p-6 w-10"><input type="checkbox" id="selectAll" class="rounded border-gray-300"></th>
                            <th class="p-6 text-[10px] font-black uppercase text-gray-400">Name / Username</th>
                            <th class="p-6 text-[10px] font-black uppercase text-gray-400">System Role</th>
                            <th class="p-6 text-[10px] font-black uppercase text-gray-400">Status</th>
                            <th class="p-6 text-[10px] font-black uppercase text-gray-400 text-right">Access Control</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td class="p-6"><input type="checkbox" name="user_ids[]" value="<?php echo $user['account_id']; ?>" class="user-checkbox rounded border-gray-300"></td>
                            <td class="p-6">
                                <p class="font-bold text-teal-900"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                <p class="text-xs text-gray-400">@<?php echo htmlspecialchars($user['username']); ?></p>
                            </td>
                            <td class="p-6 text-xs font-bold uppercase text-gray-500"><?php echo str_replace('_', ' ', $user['role']); ?></td>
                            <td class="p-6">
                                <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase <?php echo ($user['status'] === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'); ?>">
                                    <?php echo $user['status']; ?>
                                </span>
                            </td>
                            <td class="p-6 text-right">
                                <button type="button" onclick="document.getElementById('toggleForm_<?php echo $user['account_id']; ?>').submit();" class="<?php echo ($user['status'] === 'active' ? 'text-rose-600' : 'text-emerald-600'); ?> font-bold text-xs hover:underline">
                                    <?php echo ($user['status'] === 'active' ? 'Restrict' : 'Unrestrict'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <?php foreach($users as $user): ?>
            <form id="toggleForm_<?php echo $user['account_id']; ?>" method="POST" class="hidden">
                <input type="hidden" name="account_id" value="<?php echo $user['account_id']; ?>">
                <input type="hidden" name="current_status" value="<?php echo $user['status']; ?>">
                <input type="hidden" name="toggle_access" value="1">
            </form>
        <?php endforeach; ?>

        <div id="staffModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
            <div class="bg-white p-8 rounded-3xl w-full max-md shadow-2xl animate-in fade-in zoom-in duration-200">
                <div class="mb-6 flex justify-between items-center">
                    <h3 class="text-xl font-black text-teal-900 uppercase">Register Staff</h3>
                    <button onclick="closeStaffModal()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x"></i></button>
                </div>
                <form method="POST" class="space-y-4">
                    <input type="text" name="full_name" required placeholder="Full Name" class="w-full p-3 border rounded-xl font-bold">
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" name="username" required placeholder="Username" class="w-full p-3 border rounded-xl font-bold">
                        <select name="role" required class="w-full p-3 border rounded-xl font-bold">
                            <option value="admin">Administrator</option>
                            <option value="reception">Reception</option>
                            <option value="fnb_cashier">F&B Cashier</option>
                            <option value="fnb_waiter">F&B Waiter</option>
                            <option value="fnb_kitchen">F&B Kitchen</option>
                        </select>
                    </div>
                    <input type="password" name="password" required minlength="8" placeholder="Password (Min. 8 Chars)" class="w-full p-3 border rounded-xl font-bold">
                    <div class="grid grid-cols-2 gap-3 pt-4">
                        <button type="button" onclick="closeStaffModal()" class="py-4 bg-gray-100 text-gray-500 rounded-2xl font-black uppercase tracking-widest">Cancel</button>
                        <button type="submit" name="create_staff" class="py-4 bg-teal-600 text-white rounded-2xl font-black uppercase tracking-widest shadow-lg">Register</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        function openStaffModal() { document.getElementById('staffModal').classList.remove('hidden'); }
        function closeStaffModal() { document.getElementById('staffModal').classList.add('hidden'); }

        // Select/Deselect all checkboxes
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        // Validation for Bulk Action
        function confirmBulkAction() {
            const checkboxes = document.querySelectorAll('.user-checkbox:checked');
            const action = document.getElementById('actionType').value;
            
            if (checkboxes.length === 0) {
                alert("Please select at least one user.");
                return false;
            }
            if (!action) {
                alert("Please select an action to perform.");
                return false;
            }
            
            return confirm(`Are you sure you want to set ${checkboxes.length} users to ${action}?`);
        }
    </script>
</body>
</html>