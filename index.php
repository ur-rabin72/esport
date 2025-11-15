<?php
require_once "common/header.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

$message = '';
$userId = $_SESSION["id"];

// Handle joining a tournament
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['join_tournament'])) {
    $tournamentId = $_POST['tournament_id'];
    
    // Get tournament details and user balance
    $stmt = $conn->prepare("SELECT entry_fee FROM tournaments WHERE id = ?");
    $stmt->bind_param("i", $tournamentId);
    $stmt->execute();
    $tournament = $stmt->get_result()->fetch_assoc();
    $entryFee = $tournament['entry_fee'];
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $walletBalance = $user['wallet_balance'];
    $stmt->close();

    // Check if already joined
    $stmt = $conn->prepare("SELECT id FROM participants WHERE user_id = ? AND tournament_id = ?");
    $stmt->bind_param("ii", $userId, $tournamentId);
    $stmt->execute();
    $stmt->store_result();
    $alreadyJoined = $stmt->num_rows > 0;
    $stmt->close();

    if ($alreadyJoined) {
        $message = '<div class="bg-yellow-500 text-white p-3 rounded-md mb-4">You have already joined this tournament.</div>';
    } elseif ($walletBalance >= $entryFee) {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Deduct fee from user wallet
            $newBalance = $walletBalance - $entryFee;
            $stmt1 = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $stmt1->bind_param("di", $newBalance, $userId);
            $stmt1->execute();
            
            // Add participant record
            $stmt2 = $conn->prepare("INSERT INTO participants (user_id, tournament_id) VALUES (?, ?)");
            $stmt2->bind_param("ii", $userId, $tournamentId);
            $stmt2->execute();

            // Create transaction record
            $desc = "Entry fee for tournament #" . $tournamentId;
            $stmt3 = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'debit', ?)");
            $stmt3->bind_param("ids", $userId, $entryFee, $desc);
            $stmt3->execute();

            $conn->commit();
            $message = '<div class="bg-green-500 text-white p-3 rounded-md mb-4">Successfully joined the tournament!</div>';
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $message = '<div class="bg-red-500 text-white p-3 rounded-md mb-4">An error occurred. Please try again.</div>';
        }
    } else {
        $message = '<div class="bg-red-500 text-white p-3 rounded-md mb-4">Insufficient wallet balance to join.</div>';
    }
}

?>

<h2 class="text-2xl font-bold mb-4">Upcoming Tournaments</h2>
<?php echo $message; ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php
    $sql = "SELECT * FROM tournaments WHERE status = 'Upcoming' ORDER BY match_time ASC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0):
        while($row = $result->fetch_assoc()):
    ?>
    <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="p-4">
            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($row['title']); ?></h3>
            <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($row['game_name']); ?></p>
            <div class="flex justify-between items-center mt-4">
                <div>
                    <p class="text-xs text-gray-400">Prize Pool</p>
                    <p class="font-semibold text-green-400"><?php echo format_inr($row['prize_pool']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Entry Fee</p>
                    <p class="font-semibold text-red-400"><?php echo format_inr($row['entry_fee']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Match Time</p>
                    <p class="font-semibold"><?php echo date('d M, h:i A', strtotime($row['match_time'])); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-gray-700 p-4">
            <form action="index.php" method="post">
                <input type="hidden" name="tournament_id" value="<?php echo $row['id']; ?>">
                <button type="submit" name="join_tournament" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-md">Join Now</button>
            </form>
        </div>
    </div>
    <?php
        endwhile;
    else:
    ?>
    <p>No upcoming tournaments right now. Check back later!</p>
    <?php endif; $conn->close(); ?>
</div>

<?php require_once "common/bottom.php"; ?>