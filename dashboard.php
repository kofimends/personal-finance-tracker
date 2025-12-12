<?php
require_once 'config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get user's full name
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch();
$full_name = $user['first_name'] . ' ' . $user['last_name'];

// Get summary statistics
// Total accounts
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM accounts WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$accounts_count = $stmt->fetch()['count'];

// Total transactions
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$transactions_count = $stmt->fetch()['count'];

// Total balance
$stmt = $conn->prepare("SELECT SUM(balance) as total FROM accounts WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$total_balance = $stmt->fetch()['total'] ?? 0;

// Recent transactions
$stmt = $conn->prepare("SELECT t.*, c.category_name, a.account_name 
                        FROM transactions t 
                        JOIN categories c ON t.category_id = c.category_id 
                        JOIN accounts a ON t.account_id = a.account_id 
                        WHERE t.user_id = :user_id 
                        ORDER BY t.transaction_date DESC, t.created_at DESC 
                        LIMIT 5");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$recent_transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Personal Finance Tracker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        .header {
            background: #4CAF50;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-name {
            font-weight: 600;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #666;
            margin-bottom: 10px;
            font-size: 1rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #4CAF50;
        }
        
        .stat-value.income {
            color: #4CAF50;
        }
        
        .stat-value.expense {
            color: #f44336;
        }
        
        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }
        }
        
        .section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .transactions-table th {
            text-align: left;
            padding: 12px 0;
            border-bottom: 2px solid #f0f0f0;
            color: #666;
            font-weight: 600;
        }
        
        .transactions-table td {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .transaction-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .type-income {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .type-expense {
            background: #ffebee;
            color: #c62828;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            display: inline-block;
            padding: 12px 25px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        
        .action-btn:hover {
            background: #45a049;
        }
        
        .action-btn.secondary {
            background: #667eea;
        }
        
        .action-btn.secondary:hover {
            background: #5a6fd8;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state p {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Personal Finance Tracker</h1>
        <div class="user-info">
            <span class="user-name">Welcome, <?php echo htmlspecialchars($full_name); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats">
            <div class="stat-card">
                <h3>Total Balance</h3>
                <div class="stat-value">$<?php echo number_format($total_balance, 2); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Accounts</h3>
                <div class="stat-value"><?php echo $accounts_count; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Total Transactions</h3>
                <div class="stat-value"><?php echo $transactions_count; ?></div>
            </div>
        </div>
        
        <div class="actions">
            <a href="add_transaction.php" class="action-btn">Add Transaction</a>
            <a href="accounts.php" class="action-btn secondary">Manage Accounts</a>
            <a href="budgets.php" class="action-btn secondary">Manage Budgets</a>
            <a href="reports.php" class="action-btn secondary">View Reports</a>
        </div>
        
        <div class="content">
            <div class="section">
                <h2>Recent Transactions</h2>
                <?php if (count($recent_transactions) > 0): ?>
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['transaction_date']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['category_name']); ?></td>
                                    <td>
                                        <span class="transaction-type <?php echo 'type-' . $transaction['transaction_type']; ?>">
                                            <?php echo ($transaction['transaction_type'] == 'income' ? '+' : '-') . '$' . number_format($transaction['amount'], 2); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No transactions found.</p>
                        <a href="add_transaction.php" class="action-btn">Add Your First Transaction</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h2>Quick Actions</h2>
                <div style="display: grid; gap: 15px; margin-top: 20px;">
                    <a href="add_transaction.php?type=income" class="action-btn" style="text-align: center;">Add Income</a>
                    <a href="add_transaction.php?type=expense" class="action-btn secondary" style="text-align: center;">Add Expense</a>
                    <a href="categories.php" class="action-btn secondary" style="text-align: center;">Manage Categories</a>
                    <a href="profile.php" class="action-btn secondary" style="text-align: center;">Edit Profile</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>