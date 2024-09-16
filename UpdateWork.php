<?php
// エラー表示
ini_set("display_errors", 1);

// DB接続
try {
    //$pdo = new PDO('mysql:dbname=tech-27-k_kadai9;charset=utf8;host=mysql57.tech-27-k.sakura.ne.jp', 'tech-27-k', '52P34w57d3');
    $pdo = new PDO('mysql:dbname=kadai9;charset=utf8;host=localhost','root','');
} catch (PDOException $e) {
    exit('DBError:' . $e->getMessage());
}

// すべてのタスクを表示する場合
if (isset($_POST['all'])) {
    $mode = "all_tasks"; // すべてのタスク表示モード
    $start_date_filter = $_POST['start_date'] ?? null;
    $end_date_filter = $_POST['end_date'] ?? null;

    $stmt_work = $pdo->prepare("SELECT * FROM work");
    $status_work = $stmt_work->execute();

    if ($status_work == false) {
        $error = $stmt_work->errorInfo();
        exit("SQLError:" . $error[2]);
    }

    // 業務名を取得
    $works = $stmt_work->fetchAll(PDO::FETCH_ASSOC);
    $all_tasks = [];

    // 各テーブルのデータを取得
    foreach ($works as $work) {
        $tableName = "24_" . $work['id'];
        $sql = "SELECT * FROM `$tableName` WHERE 1";

        // フィルタリングのための条件を追加
        if ($start_date_filter) {
            $sql .= " AND start >= :start_date";
        }
        if ($end_date_filter) {
            $sql .= " AND finish <= :end_date";
        }

        // 開始日でソート
        $sql .= " ORDER BY start ASC";

        $stmt_task = $pdo->prepare($sql);

        // バインド値の設定
        if ($start_date_filter) {
            $stmt_task->bindValue(':start_date', $start_date_filter, PDO::PARAM_STR);
        }
        if ($end_date_filter) {
            $stmt_task->bindValue(':end_date', $end_date_filter, PDO::PARAM_STR);
        }

        $status_task = $stmt_task->execute();

        if ($status_task == false) {
            $error = $stmt_task->errorInfo();
            exit("SQLError:" . $error[2]);
        }

        // データを配列に保存
        $tasks = $stmt_task->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tasks as $task) {
            $task['workname'] = $work['workname']; // 業務名を追加
            $all_tasks[] = $task; // すべてのタスクを集約
        }
    }
}

// POSTデータを受け取ってテーブルを更新
if (isset($_POST['UpdatedWorks'])) {
    $mode = "UpdatedWorks";
    $id = $_POST['id'];  // hidden inputからIDを取得
    $workname = $_POST['workname'];
    $overview = $_POST['overview'];
    $phase = $_POST['phase'];

    // データベースの業務内容を更新する
    $sql = "UPDATE work SET workname=:workname, overview=:overview, phase=:phase WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':workname', $workname, PDO::PARAM_STR);
    $stmt->bindValue(':overview', $overview, PDO::PARAM_STR);
    $stmt->bindValue(':phase', $phase, PDO::PARAM_STR);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
    // 実行して更新が成功したかチェック
    if ($stmt->execute()) {
        echo "<p>業務内容が正常に更新されました。</p>";
    } else {
        $error = $stmt->errorInfo();
        exit("SQLError:" . $error[2]);
    }

    $mode = "UpdateWorks";
}

// 業務内容を表示および更新フォームを表示する部分
if (isset($_POST['UpdateWorks']) || $mode = "UpdateWorks") {
    $mode = "UpdateWorks";
    $stmt_work = $pdo->prepare("SELECT * FROM work");
    $status_work = $stmt_work->execute();
    if ($status_work == false) {
        $error = $stmt_work->errorInfo();
        exit("SQLError:" . $error[2]);
    }
    $works = $stmt_work->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>業務内容変更</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>
<header>
    <div class="tabs">
        <div class="tab">
            <form action="ConfirmWork.php" method="post">
                <input type="submit" name="ini" value="業務を確認" />
            </form>
        </div>
        <div class="tab">
            <form action="ConfirmWork.php" method="post">
                <input type="submit" name="all" value="すべてのタスクを表示" />
            </form>
        </div>
        <div class="tab">
            <form action="addWork.php" method="post">
                <input type="submit" value="業務を追加" />
            </form>
        </div>
        <div class="tab">
            <form action="AddTask.php" method="post">
                <input type="submit" value="タスクを追加" />
            </form>
        </div>
        <div class="tab">
            <form action="UpdateWork.php" method="post">
                <input type="submit" name="UpdateWorks" value="業務内容の変更" />
            </form>
        </div>
        <div class="tab">
        <form action="UpdateTasks.php" method="post">
                <input type="submit" name="UpdateTasks" value="タスクの変更" />
            </form>
        </div>
    </div>
</header>

    <h1>業務内容変更</h1>

    <!-- 更新用フォーム -->
    <?php if (isset($mode) && $mode == "UpdateWorks") { ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>業務名</th>
                <th>概要</th>
                <th>フェーズ</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($works as $row): ?>
            <form action="" method="post">
            <tr>
                <td><?= htmlspecialchars("24_".$row["id"]); ?></td>
                <td>
                    <input type="text" id="workname" name="workname" value="<?= htmlspecialchars($row["workname"]); ?>" required />
                </td>
                <td>
                    <textarea name="overview" id="overview" cols="50" rows="5"><?= htmlspecialchars($row["overview"]); ?></textarea>
                </td>
                <td>
                    <select id="phase" name="phase">
                        <option value="提案中" <?= ($row["phase"] == "提案中") ? 'selected' : ''; ?>>提案中</option>
                        <option value="構築中" <?= ($row["phase"] == "構築中") ? 'selected' : ''; ?>>構築中</option>
                        <option value="運用中" <?= ($row["phase"] == "運用中") ? 'selected' : ''; ?>>運用中</option>
                    </select>
                </td>
                <td>
                    <input type="hidden" id="id" name="id" value="<?= htmlspecialchars($row['id']); ?>" />
                    <input type="submit" name="UpdatedWorks" value="送信" />
                </td>
            </tr>
            </form>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php } ?>

    <?php if (isset($mode) && $mode == "UpdatedWorks") { ?>
    <form action="" method="post">
    <input type="submit" name="UpdateWorks" value="フォームに戻る" />
    </form>
    <?php } ?>

</body>
</html>