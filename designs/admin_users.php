<?php
$pageTitle = "Manage Users - Smart Driving School";

require_once __DIR__ . '/../classes/UserClass.php';
$userClass = new UserClass($conn);

// Handle Approve/Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $userId = (int) $_POST['user_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $userClass->updateUserStatus($userId, 'active');
    } elseif ($action === 'reject') {
        $userClass->updateUserStatus($userId, 'rejected');
    }

    header("Location: index.php?page=admin_users");
    exit;
}

// Fetch all users except admins
$result = $userClass->getAllUsers(null, null, 0);
$users = [];
if ($result['success'] && !empty($result['users'])) {
    $users = array_filter($result['users'], fn($u) => $u['role'] !== 'admin');
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">All Users 👥</h1>
        <a href="index.php?page=dashboard" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0"><i class="fas fa-users text-primary me-2"></i> Registered Users</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($users)): ?>
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['full_name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['phone']) ?></td>
                                <td>
                                    <span class="badge <?= $user['role'] === 'student' ? 'bg-primary' : 'bg-success' ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge 
                                        <?php if ($user['status'] === 'active') echo 'bg-success'; 
                                              elseif ($user['status'] === 'rejected') echo 'bg-danger'; 
                                              else echo 'bg-warning'; ?>">
                                        <?= ucfirst($user['status']) ?>
                                    </span>
                                </td>
                                <td><?= formatDate($user['created_at']) ?></td>
                                <td>
                                    <!-- Always show Approve/Reject buttons -->
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">No users found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
