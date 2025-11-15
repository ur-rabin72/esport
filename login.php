<?php
require_once "common/config.php";

$message = '';
$active_tab = 'login';

// Handle Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    $active_tab = 'signup';
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if(empty($username) || empty($email) || empty($password)){
        $message = '<div class="bg-red-500 text-white p-3 rounded-md mb-4">Please fill all the fields.</div>';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashed_password);
        
        if ($stmt->execute()) {
            $message = '<div class="bg-green-500 text-white p-3 rounded-md mb-4">Registration successful. Please login.</div>';
            $active_tab = 'login';
        } else {
            $message = '<div class="bg-red-500 text-white p-3 rounded-md mb-4">Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    }
}

// Handle Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $active_tab = 'login';
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if(empty($username) || empty($password)){
        $message = '<div class="bg-red-500 text-white p-3 rounded-md mb-4">Please enter username and password.</div>';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $username, $hashed_password);
            if ($stmt->fetch()) {
                if (password_verify($password, $hashed_password)) {
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $id;
                    $_SESSION["username"] = $username;
                    header("location: index.php");
                    exit;
                } else {
                    $message = '<div class="bg-red-500 text-white p-3 rounded-md mb-4">The password you entered was not valid.</div>';
                }
            }
        } else {
            $message = '<div class="bg-red-500 text-white p-3 rounded-md mb-4">No account found with that username.</div>';
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Login - Adept Play</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style> body { background-color: #111827; } </style>
</head>
<body class="bg-gray-900 text-gray-100 flex items-center justify-center h-screen">
    <div class="w-full max-w-md p-6">
        <h1 class="text-4xl font-bold text-center mb-6">Adept Play</h1>
        <?php echo $message; ?>
        <div class="bg-gray-800 rounded-lg shadow-lg">
            <div class="flex border-b border-gray-700">
                <button id="login-tab-btn" class="flex-1 py-3 text-center font-semibold border-b-2 <?php echo $active_tab == 'login' ? 'border-indigo-500 text-white' : 'border-transparent text-gray-400'; ?>" onclick="showTab('login')">Login</button>
                <button id="signup-tab-btn" class="flex-1 py-3 text-center font-semibold border-b-2 <?php echo $active_tab == 'signup' ? 'border-indigo-500 text-white' : 'border-transparent text-gray-400'; ?>" onclick="showTab('signup')">Sign Up</button>
            </div>
            
            <!-- Login Form -->
            <div id="login-tab" class="p-6 <?php echo $active_tab == 'login' ? '' : 'hidden'; ?>">
                <form action="login.php" method="post">
                    <div class="mb-4">
                        <label for="username" class="block text-sm font-bold mb-2">Username</label>
                        <input type="text" name="username" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-bold mb-2">Password</label>
                        <input type="password" name="password" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <button type="submit" name="login" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-md">Login</button>
                </form>
            </div>
            
            <!-- Signup Form -->
            <div id="signup-tab" class="p-6 <?php echo $active_tab == 'signup' ? '' : 'hidden'; ?>">
                <form action="login.php" method="post">
                    <div class="mb-4">
                        <label for="username" class="block text-sm font-bold mb-2">Username</label>
                        <input type="text" name="username" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-bold mb-2">Email</label>
                        <input type="email" name="email" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-bold mb-2">Password</label>
                        <input type="password" name="password" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <button type="submit" name="signup" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-md">Sign Up</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        function showTab(tabName) {
            document.getElementById('login-tab').classList.add('hidden');
            document.getElementById('signup-tab').classList.add('hidden');
            document.getElementById(tabName + '-tab').classList.remove('hidden');

            document.getElementById('login-tab-btn').classList.remove('border-indigo-500', 'text-white');
            document.getElementById('login-tab-btn').classList.add('border-transparent', 'text-gray-400');
            document.getElementById('signup-tab-btn').classList.remove('border-indigo-500', 'text-white');
            document.getElementById('signup-tab-btn').classList.add('border-transparent', 'text-gray-400');

            document.getElementById(tabName + '-tab-btn').classList.add('border-indigo-500', 'text-white');
            document.getElementById(tabName + '-tab-btn').classList.remove('border-transparent', 'text-gray-400');
        }
    </script>
</body>
</html>