<?php

require_once 'config.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');


$income_expense_sql = "
    SELECT 
        DATE_FORMAT(transaction_date, '%Y-%m') as month,
        SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END) as total_income,
        SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END) as total_expenses,
        SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE -amount END) as net_balance
    FROM transactions
    WHERE user_id = ?
        AND transaction_date BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
    ORDER BY month DESC
";
$income_expense_stmt = $conn->prepare($income_expense_sql);
$income_expense_stmt->execute([$user_id, $start_date, $end_date]);
$monthly_report = $income_expense_stmt->fetchAll();


$category_spending_sql = "
    SELECT 
        c.category_name,
        c.category_type,
        SUM(t.amount) as total_amount,
        COUNT(t.transaction_id) as transaction_count
    FROM transactions t
    JOIN categories c ON t.category_id = c.category_id
    WHERE t.user_id = ?
        AND t.transaction_date BETWEEN ? AND ?
        AND t.transaction_type = 'expense'
    GROUP BY c.category_id, c.category_name, c.category_type
    ORDER BY total_amount DESC
";
$category_spending_stmt = $conn->prepare($category_spending_sql);
$category_spending_stmt->execute([$user_id, $start_date, $end_date]);
$category_spending = $category_spending_stmt->fetchAll();


$account_summary_sql = "
    SELECT 
        a.account_name,
        a.account_type,
        a.balance,
        COUNT(t.transaction_id) as transaction_count,
        SUM(CASE WHEN t.transaction_type = 'income' THEN t.amount ELSE 0 END) as total_income,
        SUM(CASE WHEN t.transaction_type = 'expense' THEN t.amount ELSE 0 END) as total_expenses
    FROM accounts a
    LEFT JOIN transactions t ON a.account_id = t.account_id
    WHERE a.user_id = ?
    GROUP BY a.account_id, a.account_name, a.account_type, a.balance
    ORDER BY a.balance DESC
";
$account_summary_stmt = $conn->prepare($account_summary_sql);
$account_summary_stmt->execute([$user_id]);
$account_summary = $account_summary_stmt->fetchAll();

// Calculate totals
$total_income = 0;
$total_expenses = 0;
foreach ($monthly_report as $month) {
    $total_income += $month['total_income'];
    $total_expenses += $month['total_expenses'];
}
$net_balance = $total_income - $total_expenses;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - Personal Finance Tracker</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background-color: #f4f7f6; color: #333; line-height: 1.6; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1, h2, h3 { color: #2c3e50; margin-bottom: 20px; }
        .header { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .date-filter { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .date-form { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 200px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, button { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background-color 0.3s; border: none; cursor: pointer; }
        .btn:hover { background-color: #2980b9; }
        .btn-primary { background-color: #2ecc71; }
        .btn-primary:hover { background-color: #27ae60; }
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center; }
        .card h3 { color: #7f8c8d; font-size: 1rem; margin-bottom: 10px; }
        .card-value { font-size: 2rem; font-weight: bold; margin: 10px 0; }
        .income { color: #27ae60; }
        .expense { color: #e74c3c; }
        .net { color: #3498db; }
        .reports-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px; margin-bottom: 30px; }
        .report-section { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .nav-links { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
        .nav-links a { color: #3498db; text-decoration: none; margin: 0 10px; }
        .nav-links a:hover { text-decoration: underline; }
        .empty-state { text-align: center; padding: 40px; color: #95a5a6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Financial Reports</h1>
            <p>Analyze your income, expenses, and financial trends</p>
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>

      
        <div class="date-filter">
            <h3>Filter by Date Range</h3>
            <form method="GET" action="reports.php" class="date-form">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Reports</button>
                </div>
            </form>
        </div>


        <div class="summary-cards">
            <div class="card">
                <h3>Total Income</h3>
                <div class="card-value income">$<?php echo number_format($total_income, 2); ?></div>
                <p>From <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?></p>
            </div>
            <div class="card">
                <h3>Total Expenses</h3>
                <div class="card-value expense">$<?php echo number_format($total_expenses, 2); ?></div>
                <p>From <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?></p>
            </div>
            <div class="card">
                <h3>Net Balance</h3>
                <div class="card-value net">$<?php echo number_format($net_balance, 2); ?></div>
                <p>Income minus expenses</p>
            </div>
        </div>

      
        <div class="reports-grid">
          
            <div class="report-section">
                <h2>Monthly Summary</h2>
                <?php if (count($monthly_report) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Income</th>
                                <th>Expenses</th>
                                <th>Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_report as $month): ?>
                            <tr>
                                <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                                <td style="color: #27ae60;">$<?php echo number_format($month['total_income'], 2); ?></td>
                                <td style="color: #e74c3c;">$<?php echo number_format($month['total_expenses'], 2); ?></td>
                                <td style="color: <?php echo $month['net_balance'] >= 0 ? '#27ae60' : '#e74c3c'; ?>; font-weight: bold;">
                                    $<?php echo number_format($month['net_balance'], 2); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No transactions found for selected period.</p>
                    </div>
                <?php endif; ?>
            </div>

        
            <div class="report-section">
                <h2>Spending by Category</h2>
                <?php if (count($category_spending) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($category_spending as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                <td style="color: #e74c3c; font-weight: bold;">
                                    $<?php echo number_format($category['total_amount'], 2); ?>
                                </td>
                                <td><?php echo $category['transaction_count']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No expense data found for selected period.</p>
                    </div>
                <?php endif; ?>
            </div>

       
            <div class="report-section">
                <h2>Account Summary</h2>
                <?php if (count($account_summary) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Type</th>
                                <th>Balance</th>
                                <th>Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($account_summary as $account): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                                <td><?php echo htmlspecialchars($account['account_type']); ?></td>
                                <td style="color: <?php echo $account['balance'] >= 0 ? '#27ae60' : '#e74c3c'; ?>; font-weight: bold;">
                                    $<?php echo number_format($account['balance'], 2); ?>
                                </td>
                                <td><?php echo $account['transaction_count']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No accounts found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="nav-links">
            <a href="budgets.php">Manage Budgets</a> |
            <a href="accounts.php">Manage Accounts</a> |
            <a href="add_transaction.php">Add Transaction</a> |
            <a href="dashboard.php">Dashboard</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>
</body>
</html>
