<?php
include("function/funcs.php");
$pdo = ReadDB();


// 業務内容を表示および更新フォームを表示する部分
if (isset($_POST['UpdateWorks']) || $mode == "UpdateWorks") {
    $stmt_work = $pdo->prepare("SELECT * FROM work");
    $status_work = $stmt_work->execute();
    if ($status_work == false) {
        $error = $stmt_work->errorInfo();
        exit("SQLError:" . $error[2]);
    }
    $works = $stmt_work->fetchAll(PDO::FETCH_ASSOC);
}

// POSTデータを受け取ってテーブルを更新
if (isset($_POST['UpdatedWorks'])) {
    $mode = "UpdatedWorks";
    foreach ($_POST['work'] as $id => $data) {
        $workname = $data['workname'];
        $overview = $data['overview'];
        $phase = $data['phase'];

        // データベースの業務内容を更新する
        $sql = "UPDATE work SET workname=:workname, overview=:overview, phase=:phase WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':workname', $workname, PDO::PARAM_STR);
        $stmt->bindValue(':overview', $overview, PDO::PARAM_STR);
        $stmt->bindValue(':phase', $phase, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        // 実行して更新が成功したかチェック
        if (!$stmt->execute()) {
            $error = $stmt->errorInfo();
            exit("SQLError:" . $error[2]);
        }
    }

    echo "<p>業務内容が正常に更新されました。</p>";
}

// すべてのタスクを表示する場合
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
            <form action="AddWork.php" method="post">
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
<form action="" method="post">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>業務名</th>
                <th>概要</th>
                <th>フェーズ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($works as $row): ?>
            <tr>
                <td><?= htmlspecialchars("24_".$row["id"]); ?></td>
                <td>
                    <input type="text" name="work[<?= $row['id']; ?>][workname]" value="<?= htmlspecialchars($row["workname"]); ?>" required />
                </td>
                <td>
                    <textarea name="work[<?= $row['id']; ?>][overview]" cols="50" rows="5"><?= htmlspecialchars($row["overview"]); ?></textarea>
                </td>
                <td>
                    <select name="work[<?= $row['id']; ?>][phase]">
                        <option value="提案中" <?= ($row["phase"] == "提案中") ? 'selected' : ''; ?>>提案中</option>
                        <option value="構築中" <?= ($row["phase"] == "構築中") ? 'selected' : ''; ?>>構築中</option>
                        <option value="運用中" <?= ($row["phase"] == "運用中") ? 'selected' : ''; ?>>運用中</option>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <input type="submit" name="UpdatedWorks" value="更新" />
</form>

</body>
</html>
