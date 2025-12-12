<?php
require_once 'config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user's accounts and categories
$accounts_stmt = $conn->prepare("SELECT account_id, account_name FROM accounts WHERE user_id = :user_id ORDER BY account_name");
$accounts_stmt->bindParam(':user_id', $user_id);
$accounts_stmt->execute();
$accounts = $accounts_stmt->fetchAll();

$categories_stmt = $conn->prepare("SELECT category_id, category_name FROM categories WHERE user_id = :user_id ORDER BY category_type, category_name");
$categories_stmt->bindParam(':user_id', $user_id);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll();

// Get transaction type from URL if provided
$transaction_type = isset($_GET['type']) && in_array($_GET['type'], ['income', 'expense']) ? $_GET['type'] : '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account_id = $_POST['account_id'];
    $category_id = $_POST['category_id'];
    $transaction_type = $_POST['transaction_type'];
    $amount = $_POST['amount'];
    $description = trim($_POST['description']);
    $transaction_date = $_POST['transaction_date'];
    
    // Validation
    if (empty($account_id) || empty($category_id) || empty($amount) || empty($transaction_date)) {
        $error = 'Please fill in all required fields.';
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $error = 'Amount must be a positive number.';
    } else {
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // Insert transaction
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, account_id, category_id, transaction_type, amount, description, transaction_date) 
                                   VALUES (:user_id, :account_id, :category_id, :transaction_type, :amount, :description, :transaction_date)");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':account_id', $account_id);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':transaction_type', $transaction_type);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':transaction_date', $transaction_date);
            $stmt->execute();
            
            // Update account balance
            if ($transaction_type == 'income') {
                $update_sql = "UPDATE accounts SET balance = balance + :amount WHERE account_id = :account_id";
            } else {
                $update_sql = "UPDATE accounts SET balance = balance - :amount WHERE account_id = :account_id";
            }
            
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bindParam(':amount', $amount);
            $update_stmt->bindParam(':account_id', $account_id);
            $update_stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            $success = 'Transaction added successfully!';
            
            // Clear form if success
            if ($success) {
                $amount = $description = '';
                $transaction_date = date('Y-m-d');
            }
            
        } catch(PDOException $e) {
            $conn->rollBack();
            $error = 'Error adding transaction: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Transaction - Personal Finance Tracker</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
        }
        
        .header {
            background: #4CAF50;
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .content {
            padding: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4CAF50;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .radio-option input[type="radio"] {
            width: auto;
        }
        
        .income-option {
            color: #2e7d32;
            font-weight: 600;
        }
        
        .expense-option {
            color: #c62828;
            font-weight: 600;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn:hover {
            background: #45a049;
        }
        
        .btn-secondary {
            background: #667eea;
            margin-top: 10px;
        }
        
        .btn-secondary:hover {
            background: #5a6fd8;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }
        
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .links a {
            color: #4CAF50;
            text-decoration: none;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Personal Finance Tracker</h1>
            <p>Add New Transaction</p>
        </div>
        
        <div class="content">
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="add_transaction.php">
                <div class="form-group">
                    <label>Transaction Type</label>
                    <div class="radio-group">
                        <label class="radio-option income-option">
                            <input type="radio" name="transaction_type" value="income" <?php echo ($transaction_type == 'income' || empty($transaction_type)) ? 'checked' : ''; ?> required>
                            Income
                        </label>
                        <label class="radio-option expense-option">
                            <input type="radio" name="transaction_type" value="expense" <?php echo $transaction_type == 'expense' ? 'checked' : ''; ?> required>
                            Expense
                        </label>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="transaction_date">Date</label>
                        <input type="date" id="transaction_date" name="transaction_date" value="<?php echo isset($transaction_date) ? htmlspecialchars($transaction_date) : date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Amount ($)</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" value="<?php echo isset($amount) ? htmlspecialchars($amount) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="account_id">Account</label>
                        <select id="account_id" name="account_id" required>
                            <option value="">Select Account</option>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['account_id']; ?>" <?php echo isset($account_id) && $account_id == $account['account_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($account['account_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php echo isset($category_id) && $category_id == $category['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description (Optional)</label>
                    <textarea id="description" name="description" rows="3"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn">Add Transaction</button>
            </form>
            
            <div class="links">
                <p><a href="dashboard.php">Back to Dashboard</a> | <a href="index.php">Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>