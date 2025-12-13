
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS budgets;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS accounts;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- 1. USERS TABLE 
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. CATEGORIES TABLE 
CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_name VARCHAR(50) NOT NULL,
    category_type ENUM('income', 'expense') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. ACCOUNTS TABLE
CREATE TABLE accounts (
    account_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    account_type VARCHAR(50) NOT NULL,
    balance DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. TRANSACTIONS TABLE (income and expenses)
CREATE TABLE transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    account_id INT NOT NULL,
    category_id INT NOT NULL,
    transaction_type ENUM('income', 'expense') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. BUDGETS TABLE 
CREATE TABLE budgets (
    budget_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    monthly_amount DECIMAL(10,2) NOT NULL,
    month_year DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. LOGIN_ATTEMPTS TABLE 
CREATE TABLE login_attempts (
    attempt_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    time INT NOT NULL,
    success BOOLEAN NOT NULL,
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE INDEX idx_transactions_user_date ON transactions(user_id, transaction_date);
CREATE INDEX idx_transactions_account ON transactions(account_id);
CREATE INDEX idx_budgets_user_period ON budgets(user_id, month_year);
CREATE INDEX idx_login_attempts_user_time ON login_attempts(user_id, time);
CREATE INDEX idx_categories_user ON categories(user_id);




INSERT INTO users (username, email, password, first_name, last_name) VALUES
('demo', 'demo@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'User');


SET @user_id = LAST_INSERT_ID();


INSERT INTO categories (user_id, category_name, category_type) VALUES

(@user_id, 'Salary', 'income'),
(@user_id, 'Freelance', 'income'),
(@user_id, 'Investment', 'income'),
(@user_id, 'Gift', 'income'),
(@user_id, 'Other Income', 'income'),


(@user_id, 'Food & Dining', 'expense'),
(@user_id, 'Transportation', 'expense'),
(@user_id, 'Housing', 'expense'),
(@user_id, 'Entertainment', 'expense'),
(@user_id, 'Healthcare', 'expense'),
(@user_id, 'Shopping', 'expense'),
(@user_id, 'Education', 'expense'),
(@user_id, 'Other Expenses', 'expense');


INSERT INTO accounts (user_id, account_name, account_type, balance) VALUES
(@user_id, 'Main Checking', 'Checking', 2500.00),
(@user_id, 'Savings Account', 'Savings', 5000.00),
(@user_id, 'Credit Card', 'Credit Card', -250.00),
(@user_id, 'Cash Wallet', 'Cash', 150.00);


SET @checking_id = (SELECT account_id FROM accounts WHERE account_name = 'Main Checking' AND user_id = @user_id);
SET @savings_id = (SELECT account_id FROM accounts WHERE account_name = 'Savings Account' AND user_id = @user_id);
SET @credit_id = (SELECT account_id FROM accounts WHERE account_name = 'Credit Card' AND user_id = @user_id);


SET @salary_id = (SELECT category_id FROM categories WHERE category_name = 'Salary' AND user_id = @user_id);
SET @food_id = (SELECT category_id FROM categories WHERE category_name = 'Food & Dining' AND user_id = @user_id);
SET @transport_id = (SELECT category_id FROM categories WHERE category_name = 'Transportation' AND user_id = @user_id);
SET @housing_id = (SELECT category_id FROM categories WHERE category_name = 'Housing' AND user_id = @user_id);
SET @entertainment_id = (SELECT category_id FROM categories WHERE category_name = 'Entertainment' AND user_id = @user_id);


INSERT INTO transactions (user_id, account_id, category_id, transaction_type, amount, description, transaction_date) VALUES
-- Income transactions
(@user_id, @checking_id, @salary_id, 'income', 3000.00, 'Monthly Salary', DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(@user_id, @checking_id, @salary_id, 'income', 500.00, 'Freelance Work', DATE_SUB(CURDATE(), INTERVAL 3 DAY)),


(@user_id, @checking_id, @housing_id, 'expense', 1200.00, 'Monthly Rent', DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(@user_id, @credit_id, @food_id, 'expense', 85.50, 'Grocery Shopping', DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(@user_id, @credit_id, @transport_id, 'expense', 45.00, 'Gas Station', DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(@user_id, @checking_id, @entertainment_id, 'expense', 25.00, 'Movie Tickets', CURDATE()),
(@user_id, @savings_id, @salary_id, 'income', 200.00, 'Interest Earned', DATE_SUB(CURDATE(), INTERVAL 7 DAY));

INSERT INTO budgets (user_id, category_id, monthly_amount, month_year) VALUES
(@user_id, @food_id, 400.00, DATE_FORMAT(CURDATE(), '%Y-%m-01')),
(@user_id, @transport_id, 200.00, DATE_FORMAT(CURDATE(), '%Y-%m-01')),
(@user_id, @entertainment_id, 100.00, DATE_FORMAT(CURDATE(), '%Y-%m-01')),
(@user_id, @housing_id, 1200.00, DATE_FORMAT(CURDATE(), '%Y-%m-01'));




SELECT 'Database Tables' as description;
SHOW TABLES;


SELECT 'Table Record Counts' as description;
SELECT 'users' as table_name, COUNT(*) as record_count FROM users
UNION ALL
SELECT 'categories', COUNT(*) FROM categories
UNION ALL
SELECT 'accounts', COUNT(*) FROM accounts
UNION ALL
SELECT 'transactions', COUNT(*) FROM transactions
UNION ALL
SELECT 'budgets', COUNT(*) FROM budgets
UNION ALL
SELECT 'login_attempts', COUNT(*) FROM login_attempts;


SELECT 
    t.transaction_id,
    t.transaction_type,
    t.amount,
    t.description,
    t.transaction_date,
    c.category_name,
    a.account_name
FROM transactions t
JOIN categories c ON t.category_id = c.category_id
JOIN accounts a ON t.account_id = a.account_id
WHERE t.user_id = @user_id
ORDER BY t.transaction_date DESC
LIMIT 5;


SELECT 
    DATE_FORMAT(t.transaction_date, '%Y-%m') as month,
    SUM(CASE WHEN t.transaction_type = 'income' THEN t.amount ELSE 0 END) as total_income,
    SUM(CASE WHEN t.transaction_type = 'expense' THEN t.amount ELSE 0 END) as total_expenses,
    SUM(CASE WHEN t.transaction_type = 'income' THEN t.amount ELSE -t.amount END) as net_balance
FROM transactions t
WHERE t.user_id = @user_id
GROUP BY DATE_FORMAT(t.transaction_date, '%Y-%m')
ORDER BY month DESC;


SELECT 'Budget vs Actual Spending' as description;
SELECT 
    c.category_name,
    b.monthly_amount as budget_amount,
    COALESCE(SUM(t.amount), 0) as actual_spending,
    b.monthly_amount - COALESCE(SUM(t.amount), 0) as remaining
FROM budgets b
JOIN categories c ON b.category_id = c.category_id
LEFT JOIN transactions t ON b.category_id = t.category_id 
    AND t.user_id = b.user_id 
    AND t.transaction_type = 'expense'
    AND DATE_FORMAT(t.transaction_date, '%Y-%m') = DATE_FORMAT(b.month_year, '%Y-%m')
WHERE b.user_id = @user_id 
    AND b.month_year = DATE_FORMAT(CURDATE(), '%Y-%m-01')
GROUP BY c.category_name, b.monthly_amount;
