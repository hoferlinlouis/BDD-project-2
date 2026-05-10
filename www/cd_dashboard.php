<!DOCTYPE html>
<?php
try {
    $bdd = new PDO('mysql:host=db;dbname=group10;charset=utf8mb4', 'group10', 'secret', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die('Connection failed: ' . htmlspecialchars($e->getMessage()));
}

$query_sql = "
    WITH RECURSIVE genre_closure AS (
        SELECT NAME AS genre, NAME AS related_genre
        FROM GENRE
        UNION
        SELECT GC.genre, SP.GENRE AS related_genre
        FROM genre_closure GC
        JOIN SPECIALIZES SP ON SP.SUBGENRE = GC.related_genre
    ),
    song_stats AS (
        SELECT
            CD_NUMBER,
            SEC_TO_TIME(SUM(TIME_TO_SEC(DURATION))) AS total_duration,
            SEC_TO_TIME(MIN(TIME_TO_SEC(DURATION))) AS min_duration,
            SEC_TO_TIME(MAX(TIME_TO_SEC(DURATION))) AS max_duration,
            SEC_TO_TIME(AVG(TIME_TO_SEC(DURATION))) AS avg_duration
        FROM SONG
        GROUP BY CD_NUMBER
    ),
    playlist_counts AS (
        SELECT CD_NUMBER, COUNT(*) AS playlist_appearances
        FROM CONTAINS
        GROUP BY CD_NUMBER
    ),
    cd_genres AS (
        SELECT
            S.CD_NUMBER,
            GROUP_CONCAT(DISTINCT GC.related_genre ORDER BY GC.related_genre SEPARATOR ', ') AS associated_genres
        FROM SONG S
        JOIN genre_closure GC ON GC.genre = S.GENRE
        GROUP BY S.CD_NUMBER
    )
    SELECT
        C.CD_NUMBER,
        C.TITLE,
        SS.total_duration,
        SS.min_duration,
        SS.max_duration,
        SS.avg_duration,
        COALESCE(PC.playlist_appearances, 0) AS playlist_appearances,
        CG.associated_genres
    FROM CD C
    LEFT JOIN song_stats SS ON SS.CD_NUMBER = C.CD_NUMBER
    LEFT JOIN playlist_counts PC ON PC.CD_NUMBER = C.CD_NUMBER
    LEFT JOIN cd_genres CG ON CG.CD_NUMBER = C.CD_NUMBER
    ORDER BY C.TITLE ASC
";

try {
    $stmt = $bdd->query($query_sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('<h3>SQL Error: ' . htmlspecialchars($e->getMessage()) . '</h3>');
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CD Dashboard</title>
    <style>
        body { margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #eee; }
        .muted { color: #777; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <a href="index.php">Back to Home</a>
    <h1>CD Dashboard</h1>
    <p>Statistics for each CD: song durations, playlist appearances, and associated genres.</p>

    <table>
        <thead>
            <tr>
                <th>CD</th>
                <th>Total Duration</th>
                <th>Min Duration</th>
                <th>Max Duration</th>
                <th>Avg Duration</th>
                <th>Playlist Appearances</th>
                <th>Associated Genres</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($results): ?>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['CD_NUMBER'] . ' - ' . $row['TITLE']) ?></td>
                        <td><?= htmlspecialchars($row['total_duration'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['min_duration'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['max_duration'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['avg_duration'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['playlist_appearances']) ?></td>
                        <td><?= $row['associated_genres'] ? htmlspecialchars($row['associated_genres']) : '<span class="muted">No genres</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td class="center muted" colspan="7">No CDs found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
