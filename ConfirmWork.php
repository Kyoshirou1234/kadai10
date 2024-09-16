<?php
// エラー表示
include("function/funcs.php");
$pdo = ReadDB();
$mode = "ini";

// すべてのタスクを表示する場合
if (isset($_POST['all'])) {
    $mode = "all_tasks"; // すべてのタスク表示モード
    $start_date_filter = $_POST['start_date'] ?? null;
    $end_date_filter = $_POST['end_date'] ?? null;
    $selected_workname = $_POST['workname'] ?? null;

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
        if ($selected_workname && $selected_workname !== $work['workname']) {
            continue; // 選択された業務名と異なる場合はスキップ
        }

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

// 特定のタスクを表示する場合
elseif (isset($_POST['select']) && isset($_POST['id'])) {
    $mode = "task";
    $id = $_POST['id'];
    $work = $_POST['work'];

    $stmt_task = $pdo->prepare("SELECT * FROM `$id` ORDER BY start ASC");
    $status_task = $stmt_task->execute();
    if ($status_task == false) {
        $error = $stmt_task->errorInfo();
        exit("SQLError:" . $error[2]);
    }

    // データ取得
    $task = $stmt_task->fetchAll(PDO::FETCH_ASSOC);
}

// 初期画面: 業務一覧表示
else {
    $mode = "ini";
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
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>回答状況</title>
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

    <?php if ($mode == "ini") { ?>
    <!-- 業務名一覧を表示 -->
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
                <td>
                    <form action="" method="post">
                        <input type="hidden" name="id" value="<?= htmlspecialchars("24_" . $row['id']); ?>">
                        <input type="hidden" name="work" value="<?= htmlspecialchars($row['workname']); ?>">
                        <input type="submit" name="select" value="<?= htmlspecialchars("24_" . $row['id']); ?>">
                    </form>
                </td>
                <td><?= htmlspecialchars($row["workname"]); ?></td>
                <td><?= htmlspecialchars($row["overview"]); ?></td>
                <td><?= htmlspecialchars($row["phase"]); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php } ?>

    <?php if ($mode == "all_tasks") { ?>
    <!-- すべてのタスクを表示 -->
    <header>
        <form action="" method="post">
            <label for="start_date">開始日から:</label>
            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date_filter ?? '') ?>">

            <label for="end_date">終了日まで:</label>
            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date_filter ?? '') ?>">

            <label for="workname">業務名:</label>
            <select id="workname" name="workname">
                <option value="">全て</option>
                <?php foreach ($works as $work): ?>
                <option value="<?= htmlspecialchars($work['workname']); ?>" <?= ($selected_workname == $work['workname']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($work['workname']); ?>
                </option>
                <?php endforeach; ?>
            </select>

            <input type="submit" name="all" value="フィルターをかける" />
        </form>
    </header>

    <h2>すべてのタスク</h2>
    <table>
        <thead>
            <tr>
                <th>業務名</th>
                <th>タスク名</th>
                <th>開始日</th>
                <th>終了日</th>
                <th>重要度</th>
                <th>済</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($all_tasks as $task): ?>
            <tr>
                <td><?= htmlspecialchars($task["workname"]); ?></td>
                <td><?= htmlspecialchars($task["task"]); ?></td>
                <td><?= htmlspecialchars($task["start"]); ?></td>
                <td><?= htmlspecialchars($task["finish"]); ?></td>
                <td><?= htmlspecialchars($task["importance"]); ?></td>
                <td><?= htmlspecialchars($task["done"]); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php } ?>

    <?php if ($mode == "task") { ?>
    <!-- 特定の業務タスクを表示 -->
    <h2><?= htmlspecialchars($work); ?>のタスク</h2>
    <table>
        <thead>
            <tr>
                <th>タスク名</th>
                <th>開始日</th>
                <th>終了日</th>
                <th>重要度</th>
                <th>完了</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($task as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row["task"]); ?></td>
                <td><?= htmlspecialchars($row["start"]); ?></td>
                <td><?= htmlspecialchars($row["finish"]); ?></td>
                <td><?= htmlspecialchars($row["importance"]); ?></td>
                <td><?= htmlspecialchars($row["done"]); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php } ?>
</body>
</html>