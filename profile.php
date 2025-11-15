<?php
require_once "common/header.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

$userId = $_SESSION["id"];
$message = '';
$pwd_message = '';

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $username, $email, $userId);
    if ($stmt->execute()) {
        $_SESSION['username'] = $username;
        $message = '<div class="bg-green-500 text-white p-2 rounded-md mb-4 text-sm">Profile updated successfully.</div>';
    } else {
        $message = '<div class="bg-red-500 text-white p-2 rounded-md mb-4 text-sm">Error updating profile.</div>';
    }
    $stmt->close();
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $pwd_message = '<div class="bg-red-500 text-white p-2 rounded-md mb-4 text-sm">Passwords do not match.</div>';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $userId);
        if ($stmt->execute()) {
            $pwd_message = '<div class="bg-green-500 text-white p-2 rounded-md mb-4 text-sm">Password changed successfully.</div>';
        } else {
            $pwd_message = '<div class="bg-red-500 text-white p-2 rounded-md mb-4 text-sm">Error changing password.</div>';
        }
        $stmt->close();
    }
}

// Get user info
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
?>

<div class="space-y-6">
    <!-- Edit Profile Section -->
    <div class="bg-gray-800 p-4 rounded-lg">
        <h3 class="text-xl font-bold mb-4">Edit Profile</h3>
        <?php echo $message; ?>
        <form action="profile.php" method="post" class="space-y-4">
            <div>
                <label for="username" class="block text-sm font-bold mb-2">Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md">
            </div>
            <div>
                <label for="email" class="block text-sm font-bold mb-2">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md">
            </div>
            <button type="submit" name="update_profile" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-md">Save Changes</button>
        </form>
    </div>

    <!-- Change Password Section -->
    <div class="bg-gray-800 p-4 rounded-lg">
        <h3 class="text-xl font-bold mb-4">Change Password</h3>
        <?php echo $pwd_message; ?>
        <form action="profile.php" method="post" class="space-y-4">
            <div>
                <label for="new_password" class="block text-sm font-bold mb-2">New Password</label>
                <input type="password" name="new_password" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md">
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-bold mb-2">Confirm New Password</label>
                <input type="password" name="confirm_password" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md">
            </div>
            <button type="submit" name="change_password" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-md">Update Password</button>
        </form>
    </div>

    <!-- Logout -->
    <a href="logout.php" class="block text-center w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-md">Logout</a>
</div>


<?php require_once "common/bottom.php"; ?>