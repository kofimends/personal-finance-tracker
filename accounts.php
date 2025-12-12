<?php
// accounts.php - Manage Financial Accounts
require_once 'config.php';

// Simple authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle form submission to add a new account
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_account'])) {
        $account_name = trim($_POST['account_name']);
        $account_type = $_POST['account_type'];
        $initial_balance = $_POST['initial_balance'];
        
        if (!empty($account_name) && !empty($account_type)) {
            try {
                $sql = "INSERT INTO accounts (user_id, account_name, account_type, balance) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$user_id, $account_name, $account_type, $initial_balance]);
                $message = "Account added successfully!";
            } catch (PDOException $e) {
                $message = "Error: Could not add account.";
            }
        }
    }
    
    // Handle delete account
    if (isset($_POST['delete_account'])) {
        $account_id = $_POST['account_id'];
        
        try {
            // Check if account has transactions
            $check_sql = "SELECT COUNT(*) as count FROM transactions WHERE account_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$account_id]);
            $result = $check_stmt->fetch();
            
            if ($result['count'] == 0) {
                $delete_sql = "DELETE FROM accounts WHERE account_id = ? AND user_id = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->execute([$account_id, $user_id]);
                $message = "Account deleted successfully!";
            } else {
                $message = "Cannot delete account with existing transactions.";
            }
        } catch (PDOException $e) {
            $message = "Error: Could not delete account.";
        }
    }
}

// Fetch user's accounts with balance information
$accounts_sql = "
    SELECT 
        a.account_id,
        a.account_name,
        a.account_type,
        a.balance,
        a.created_at,
        COUNT(t.transaction_id) as transaction_count
    FROM accounts a
    LEFT JOIN transactions t ON a.account_id = t.account_id
    WHERE a.user_id = ?
    GROUP BY a.account_id, a.account_name, a.account_type, a.balance, a.created_at
    ORDER BY a.created_at DESC
";
$accounts = $conn->prepare($accounts_sql);
$accounts->execute([$user_id]);
$account_list = $accounts->fetchAll();

// Calculate total balance
$total_balance = 0;
foreach ($account_list as $account) {
    $total_balance += $account['balance'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts - Personal Finance Tracker</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background-color: #f4f7f6; color: #333; line-height: 1.6; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        h1, h2 { color: #2c3e50; margin-bottom: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #ecf0f1; padding-bottom: 15px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background-color 0.3s; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { background-color: #2980b9; }
        .btn-primary { background-color: #2ecc71; }
        .btn-primary:hover { background-color: #27ae60; }
        .btn-danger { background-color: #e74c3c; }
        .btn-danger:hover { background-color: #c0392b; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-section, .list-section { margin-bottom: 40px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 200px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .balance-positive { color: #27ae60; font-weight: bold; }
        .balance-negative { color: #e74c3c; font-weight: bold; }
        .total-balance { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; text-align: center; font-size: 1.2rem; }
        .nav-links { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
        .nav-links a { color: #3498db; text-decoration: none; margin: 0 10px; }
        .nav-links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage Your Accounts</h1>
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') === false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Total Balance Summary -->
        <div class="total-balance">
            <h3>Total Balance Across All Accounts</h3>
            <p style="font-size: 2rem; color: <?php echo $total_balance >= 0 ? '#27ae60' : '#e74c3c'; ?>">
                $<?php echo number_format($total_balance, 2); ?>
            </p>
        </div>

        <!-- Form to Add a New Account -->
        <div class="form-section">
            <h2>Add New Account</h2>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="account_name">Account Name</label>
                        <input type="text" id="account_name" name="account_name" placeholder="e.g., Chase Checking" required>
                    </div>
                    <div class="form-group">
                        <label for="account_type">Account Type</label>
                        <select id="account_type" name="account_type" required>
                            <option value="">Select Type</option>
                            <option value="Checking">Checking Account</option>
                            <option value="Savings">Savings Account</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Cash">Cash</option>
                            <option value="Investment">Investment</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="initial_balance">Initial Balance ($)</label>
                        <input type="number" id="initial_balance" name="initial_balance" step="0.01" value="0.00">
                    </div>
                </div>
                <button type="submit" name="add_account" class="btn btn-primary">Add Account</button>
            </form>
        </div>

        <!-- List of Existing Accounts -->
        <div class="list-section">
            <h2>Your Accounts</h2>
            <?php if (count($account_list) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Account Name</th>
                            <th>Type</th>
                            <th>Current Balance</th>
                            <th>Transactions</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($account_list as $account): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                            <td><?php echo htmlspecialchars($account['account_type']); ?></td>
                            <td class="<?php echo $account['balance'] >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
                                $<?php echo number_format($account['balance'], 2); ?>
                            </td>
                            <td><?php echo $account['transaction_count']; ?> transactions</td>
                            <td><?php echo date('M d, Y', strtotime($account['created_at'])); ?></td>
                            <td>
                                <?php if ($account['transaction_count'] == 0): ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="account_id" value="<?php echo $account['account_id']; ?>">
                                    <button type="submit" name="delete_account" class="btn btn-danger" 
                                            onclick="return confirm('Delete this account?')">Delete</button>
                                </form>
                                <?php else: ?>
                                <span style="color: #95a5a6; font-size: 0.9rem;">Has transactions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>You haven't added any accounts yet. Add your first account using the form above.</p>
            <?php endif; ?>
        </div>

        <div class="nav-links">
            <a href="budgets.php">Manage Budgets</a> |
            <a href="reports.php">View Reports</a> |
            <a href="add_transaction.php">Add Transaction</a> |
            <a href="dashboard.php">Dashboard</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>
</body>
</html>