<?PHP 

function ReadDB(){
// エラー表示
ini_set("display_errors", 1);

// DB接続
try {
    $pdo = new PDO('mysql:dbname=kadai9;charset=utf8;host=localhost','root','');
} catch (PDOException $e) {
    exit('DBError:' . $e->getMessage());
}

return $pdo;
}

?>