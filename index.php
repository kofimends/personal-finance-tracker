<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Finance Tracker - CIS 344 Project</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.8rem;
            margin-bottom: 10px;
            font-weight: 300;
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        .main-content {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .feature {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .feature:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }
        
        .feature h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.4rem;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .feature p {
            color: #555;
            line-height: 1.7;
        }
        
        .actions {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
            margin-top: 40px;
        }
        
        .actions h2 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }
        
        .button-group {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 14px 32px;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .login-btn {
            background: #2ecc71;
            color: white;
        }
        
        .login-btn:hover {
            background: #27ae60;
            transform: translateY(-2px);
        }
        
        .register-btn {
            background: #3498db;
            color: white;
        }
        
        .register-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .test-btn {
            background: #95a5a6;
            color: white;
        }
        
        .test-btn:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 30px 20px;
            margin-top: 60px;
        }
        
        .footer p {
            margin: 10px 0;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2.2rem;
            }
            
            .button-group {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Personal Finance Tracker</h1>
        <p>Manage your income, expenses, and budgets with our secure financial management system. Built for CIS 344 Database Project at Lehman College.</p>
    </div>
    
    <div class="main-content">
        <div class="features-grid">
            <div class="feature">
                <h3>Income Management</h3>
                <p>Track all sources of income with detailed categorization and historical reporting. Monitor your earnings trends over time.</p>
            </div>
            
            <div class="feature">
                <h3>Expense Tracking</h3>
                <p>Record daily expenses across customizable categories. Get insights into your spending habits and identify saving opportunities.</p>
            </div>
            
            <div class="feature">
                <h3>Budget Planning</h3>
                <p>Set monthly budgets for different categories and track your progress. Receive alerts when approaching budget limits.</p>
            </div>
            
            <div class="feature">
                <h3>Secure Access</h3>
                <p>Individual user accounts with encrypted authentication. Your financial data remains private and protected at all times.</p>
            </div>
        </div>
        
        <div class="actions">
            <h2>Start Managing Your Finances</h2>
            <div class="button-group">
                <a href="login.php" class="btn login-btn">Login to Account</a>
                <a href="register.php" class="btn register-btn">Create New Account</a>
                <a href="test_db.php" class="btn test-btn">Test Database</a>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>Personal Finance Tracker &copy; 2025</p>
        <p>CIS 344 Database Project | Lehman College, CUNY</p>
    </div>
</body>
</html>