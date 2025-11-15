<?php
require_once "common/header.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

$userId = $_SESSION["id"];

// Get user wallet balance
$stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

?>

<div class="bg-indigo-600 rounded-lg p-6 text-center mb-6 shadow-lg">
    <p class="text-lg text-indigo-200">Current Balance</p>
    <p class="text-5xl font-bold mt-2"><?php echo format_inr($user['wallet_balance']); ?></p>
</div>

<div class="flex justify-around mb-6">
    <button class="bg-green-500 text-white font-bold py-3 px-6 rounded-lg w-2/5">Add Money</button>
    <button class="bg-red-500 text-white font-bold py-3 px-6 rounded-lg w-2/5">Withdraw</button>
</div>

<h3 class="text-xl font-bold mb-4">Transaction History</h3>
<div class="space-y-3">
    <?php
    $sql = "SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0):
        while($row = $result->fetch_assoc()):
    ?>
    <div class="bg-gray-800 rounded-lg p-4 flex justify-between items-center">
        <div>
            <p class="font-semibold"><?php echo htmlspecialchars($row['description']); ?></p>
            <p class="text-sm text-gray-400"><?php echo date('d M, Y h:i A', strtotime($row['created_at'])); ?></p>
        </div>
        <div class="font-bold text-lg <?php echo $row['type'] == 'credit' ? 'text-green-400' : 'text-red-400'; ?>">
            <?php echo ($row['type'] == 'credit' ? '+' : '-') . format_inr($row['amount']); ?>
        </div>
    </div>
    <?php
        endwhile;
    else:
        echo "<p>No transactions found.</p>";
    endif;
    $stmt->close();
    $conn->close();
    ?>
</div>

<?php require_once "common/bottom.php"; ?>