<?php
include("function/funcs.php");
$pdo = ReadDB();

// フィルター条件
$filter_workname = isset($_POST['filter_workname']) ? $_POST['filter_workname'] : '';

// タスクの更新処理
if (isset($_POST['UpdatedTask'])) {
    
    // 全タスクを一括更新
    foreach ($_POST['task_data'] as $task_key => $task_data) {
        // タスクデータを分解
        list($id, $tablename) = explode('|', $task_data);

        // フォームからの値を取得
        $task_name = $_POST['task'][$task_key];
        $start = $_POST['start'][$task_key];
        $finish = $_POST['finish'][$task_key];
        $importance = $_POST['importance'][$task_key];
        $done = $_POST['done'][$task_key];

        // データベースのタスクを更新する
        $sql = "UPDATE $tablename SET task=:task, start=:start, finish=:finish, importance=:importance, done=:done WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':task', $task_name, PDO::PARAM_STR);
        $stmt->bindValue(':start', $start, PDO::PARAM_STR);
        $stmt->bindValue(':finish', $finish, PDO::PARAM_STR);
        $stmt->bindValue(':importance', $importance, PDO::PARAM_STR);
        $stmt->bindValue(':done', $done, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            $error = $stmt->errorInfo();
            exit("SQLError: " . $error[2]);
        }
    }

    // タスクの削除処理
    if (isset($_POST['DeleteTasks'])) {
        $delete_tasks = $_POST['DeleteTasks'];
        
        foreach ($delete_tasks as $task_data) {
            list($id, $tablename) = explode('|', $task_data);

            // データベースから該当タスクを削除するSQL文
            $sql_delete = "DELETE FROM $tablename WHERE id = :id";
            $stmt_delete = $pdo->prepare($sql_delete);
            $stmt_delete->bindValue(':id', $id, PDO::PARAM_INT);

            if (!$stmt_delete->execute()) {
                $error = $stmt_delete->errorInfo();
                exit("SQLError: " . $error[2]);
            }
        }

        $message = "<div>選択したタスクが削除されました。</div>";
    }
}

// 業務名を取得
$stmt_work = $pdo->prepare("SELECT * FROM work");
$status_work = $stmt_work->execute();

if ($status_work == false) {
    $error = $stmt_work->errorInfo();
    exit("SQLError:" . $error[2]);
}

$works = $stmt_work->fetchAll(PDO::FETCH_ASSOC);
$all_tasks = [];

// 各テーブルのデータを取得
foreach ($works as $work) {
    if ($filter_workname && $filter_workname !== $work['workname']) {
        continue; // 選択された業務名と異なる場合はスキップ
    }

    $tableName = "24_" . $work['id'];
    $sql = "SELECT * FROM `$tableName` WHERE 1";
    $stmt_task = $pdo->prepare($sql);
    $status_task = $stmt_task->execute();
    
    if ($status_task == false) {
        $error = $stmt_task->errorInfo();
        exit("SQLError:" . $error[2]);
    }

    // データを配列に保存
    $tasks = $stmt_task->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tasks as $task) {
        $task['workname'] = $work['workname']; // 業務名を追加
        $task['tablename'] = $tableName; // テーブル名を追加
        $all_tasks[] = $task; // すべてのタスクを集約
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>タスク変更</title>
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

<h1>タスクアップデート</h1>
<!-- フィルターフォーム -->
<form method="post" action="">
    <label for="filter_workname">業務名:</label>
    <select id="filter_workname" name="filter_workname">
        <option value="">すべて</option>
        <?php foreach ($works as $work): ?>
            <option value="<?= htmlspecialchars($work['workname']); ?>" <?= $filter_workname === $work['workname'] ? 'selected' : ''; ?>>
                <?= htmlspecialchars($work['workname']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="submit" value="フィルター">
</form>
<!-- 更新用フォーム -->
<form method="post" action="">
    <table>
        <thead>
        <tr>
            <th>業務名</th>
            <th>タスク名</th>
            <th>開始日</th>
            <th>終了日</th>
            <th>重要度</th>
            <th>済</th>
            <th>削除</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($all_tasks as $task): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($task["workname"]); ?>
                    <input type="hidden" name="task_data[<?= htmlspecialchars($task['tablename'] . $task['id']); ?>]" value="<?= htmlspecialchars($task['id'] . '|' . $task['tablename']); ?>">
                </td>
                <td><input type="text" name="task[<?= htmlspecialchars($task['tablename'] . $task['id']); ?>]" value="<?= htmlspecialchars($task["task"]); ?>"></td>
                <td><input type="date" name="start[<?= htmlspecialchars($task['tablename'] . $task['id']); ?>]" value="<?= htmlspecialchars($task["start"]); ?>"></td>
                <td><input type="date" name="finish[<?= htmlspecialchars($task['tablename'] . $task['id']); ?>]" value="<?= htmlspecialchars($task["finish"]); ?>"></td>
                <td>
                    <select name="importance[<?= htmlspecialchars($task['tablename'] . $task['id']); ?>]">
                        <option value="低" <?= $task["importance"] === "低" ? 'selected' : ''; ?>>低</option>
                        <option value="中" <?= $task["importance"] === "中" ? 'selected' : ''; ?>>中</option>
                        <option value="高" <?= $task["importance"] === "高" ? 'selected' : ''; ?>>高</option>
                    </select>
                </td>
                <td>
    <input type="hidden" name="done[<?= htmlspecialchars($task['tablename'] . $task['id']); ?>]" value="未">
    <label>
        <input type="checkbox" name="done[<?= htmlspecialchars($task['tablename'] . $task['id']); ?>]" value="済" <?= $task["done"] === "済" ? 'checked' : ''; ?>>
    </label>
</td>
<td>
    <input type="checkbox" name="DeleteTasks[<?= htmlspecialchars($task['tablename'] . $task['id']); ?>]" value="<?= htmlspecialchars($task['id'] . '|' . $task['tablename']); ?>">
</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <!-- 全タスク更新用の1つの更新ボタン -->
    <input type="submit" name="UpdatedTask" value="更新">
</form>
</body>
</html>