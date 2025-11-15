<?php
require_once "common/header.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

$userId = $_SESSION["id"];
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'upcoming';
?>

<div class="mb-4 border-b border-gray-700">
    <ul class="flex -mb-px">
        <li class="mr-2">
            <a href="?tab=upcoming" class="inline-block p-4 border-b-2 rounded-t-lg <?php echo $active_tab == 'upcoming' ? 'border-indigo-500 text-indigo-400' : 'border-transparent hover:text-gray-300 hover:border-gray-500'; ?>">Upcoming/Live</a>
        </li>
        <li class="mr-2">
            <a href="?tab=completed" class="inline-block p-4 border-b-2 rounded-t-lg <?php echo $active_tab == 'completed' ? 'border-indigo-500 text-indigo-400' : 'border-transparent hover:text-gray-300 hover:border-gray-500'; ?>">Completed</a>
        </li>
    </ul>
</div>

<div>
    <?php if ($active_tab == 'upcoming'): ?>
        <div class="space-y-4">
        <?php
        $sql = "SELECT t.* FROM tournaments t JOIN participants p ON t.id = p.tournament_id WHERE p.user_id = ? AND t.status IN ('Upcoming', 'Live') ORDER BY t.match_time ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0):
            while($row = $result->fetch_assoc()):
        ?>
            <div class="bg-gray-800 rounded-lg p-4">
                <h3 class="text-xl font-bold"><?php echo htmlspecialchars($row['title']); ?></h3>
                <p class="text-gray-400"><?php echo htmlspecialchars($row['game_name']); ?></p>
                <div class="mt-2 text-sm">
                    <p><strong>Status:</strong> <span class="font-bold <?php echo $row['status'] == 'Live' ? 'text-green-400' : 'text-yellow-400'; ?>"><?php echo $row['status']; ?></span></p>
                    <p><strong>Match Time:</strong> <?php echo date('d M, h:i A', strtotime($row['match_time'])); ?></p>
                </div>
                <?php if ($row['status'] == 'Live' && !empty($row['room_id'])): ?>
                <div class="mt-4 bg-gray-700 p-3 rounded-md">
                    <p><strong>Room ID:</strong> <span class="font-mono text-lg"><?php echo htmlspecialchars($row['room_id']); ?></span></p>
                    <p><strong>Password:</strong> <span class="font-mono text-lg"><?php echo htmlspecialchars($row['room_password']); ?></span></p>
                </div>
                <?php endif; ?>
            </div>
        <?php
            endwhile;
        else:
            echo "<p>You haven't joined any upcoming tournaments.</p>";
        endif;
        $stmt->close();
        ?>
        </div>
    <?php else: ?>
        <div class="space-y-4">
        <?php
        $sql = "SELECT t.*, (CASE WHEN t.winner_id = ? THEN 'Winner' ELSE 'Participated' END) as result_status FROM tournaments t JOIN participants p ON t.id = p.tournament_id WHERE p.user_id = ? AND t.status = 'Completed' ORDER BY t.match_time DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0):
            while($row = $result->fetch_assoc()):
        ?>
            <div class="bg-gray-800 rounded-lg p-4">
                <h3 class="text-xl font-bold"><?php echo htmlspecialchars($row['title']); ?></h3>
                <div class="mt-2 text-sm flex justify-between">
                    <p><strong>Result:</strong> <span class="font-bold <?php echo $row['result_status'] == 'Winner' ? 'text-green-400' : 'text-gray-400'; ?>"><?php echo $row['result_status']; ?></span></p>
                    <p><strong>Prize Pool:</strong> <?php echo format_inr($row['prize_pool']); ?></p>
                </div>
            </div>
        <?php
            endwhile;
        else:
            echo "<p>No completed tournaments found.</p>";
        endif;
        $stmt->close();
        ?>
        </div>
    <?php endif; $conn->close(); ?>
</div>


<?php require_once "common/bottom.php"; ?>