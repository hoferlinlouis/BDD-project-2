<?php
// connection
try {
    $bdd = new PDO('mysql:host=db;dbname=group10;charset=utf8mb4', 'group10', 'secret', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$query = "
    SELECT 
        E.NAME, 
        E.DATE, 
        COUNT(R.NAME) as nbr_demands,
        SUM(R.PRICE) as total_demand_value
    FROM EVENT E
    LEFT JOIN REQUEST R ON E.ID = R.EVENT_ID
    GROUP BY E.ID
    ORDER BY E.DATE DESC, E.NAME ASC
";

try {
    $events = $bdd->query($query)->fetchAll();
} catch (PDOException $e) {
    die("SQL Error: " . $e->getMessage());
}

$today = date('Y-m-d');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event board</title>
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; }
        .status-past { color: gray; font-weight: bold; }
        .status-today { color: green; font-weight: bold; }
        .status-future { color: blue; font-weight: bold; }
    </style>
</head>
<body>
    <a href="index.php">← Back to Home</a>
    <h1>Event board</h1>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Event Name</th>
                <th>Status</th>
                <th>Demands</th>
                <th>Total Cost (EUR)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $event): 
                // statut
                if ($event['DATE'] < $today) {
                    $status = "PASSÉ";
                    $class = "status-past";
                } elseif ($event['DATE'] == $today) {
                    $status = "AUJOURD'HUI";
                    $class = "status-today";
                } else {
                    $status = "FUTUR";
                    $class = "status-future";
                }

                // coût
                $fixed_fee = 1500;
                $commission = ($event['total_demand_value'] ?? 0) * 0.05;
                // 1500€ + 5% of demande
                $total_cost = $fixed_fee + $commission;
            ?>
            <tr>
                <td><?= htmlspecialchars($event['DATE']) ?></td>
                <td><?= htmlspecialchars($event['NAME']) ?></td>
                <td class="<?= $class ?>"><?= $status ?></td>
                <td><?= htmlspecialchars($event['nbr_demands']) ?></td>
                <td><?= number_format($total_cost, 2, '.', ' ') ?> €</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>