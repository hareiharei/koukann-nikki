<?php
session_start();

//データベース接続
include "database_connect.php";
dbc();

/*$sql = 'CREATE TABLE IF NOT EXSISTS users'
.'('
.'id INT AUTO_INCREMENT PRIMARY KEY,'
.'username CHAR(20)'
.'password CHAR(20)'
.');';
$stmt = $pdo -> query($sql);*/

//変数の初期設定
$error_message = "";
$database_password = "";
//$table = "";  //テスト用

//showtable(); //テスト用

//ログイン
if (isset($_POST['login'])) {   //ログインボタンが押されたら
    if (!empty($_POST['username'])) {   //ユーザーネームが入力されているか確認
        if (!empty($_POST['password'])) {   //パスワードが入力されているか確認
            //入力されたユーザーネームを取得
            $input_username = $_POST['username'];

            //データベースに入力されたユーザーネームがあるか確認
            $sql = 'SELECT * FROM users WHERE username=:username';
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':username', $input_username, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result !== false) {
                $id = $result['id'];
                $database_password = $result['password'];
            } else {    //入力されたユーザーネームがデータベースに存在しなかった場合
                $id = '';
                $database_password = '';
            }

            if (password_verify($_POST['password'], $database_password)) {  //パスワードとユーザーネームともに一致した場合
                //マイページに移動
                $login_completed = true;
                $_SESSION['username'] = $input_username;
                $_SESSION['id'] = $id;
                header("Location: mypage.php");
                exit;

                //$error_message = "ログインが完了しました";  //テスト用

            } elseif (empty($database_password)) {  //パスワードが存在しない(ユーザーネームも存在しない)場合 
                $error_message = "このユーザーネームは登録されていません";
            } else {  //パスワードが存在するがパスワードが一致しない場合
                $error_message = "パスワードが間違っています";
            }
        } else {
            $error_message = "パスワードが入力されていません";
        }
    } elseif (!empty($_POST['password'])) {  //ユーザーネームは入力されていないが、パスワードは入力されている場合
        $error_message = "ユーザーネームが入力されていません";
    } else {  //ユーザーネームもパスワードも入力されていない場合
        $error_message = "ユーザーネームとパスワードが入力されていません";
    }
} else {
    $error_message = "";
}

//showtable(); //テスト用

//テスト用
/*function showtable(){
    global $table;
    global $sql;
    global $pdo;
    $table = "";
    $sql = 'SELECT * FROM users';
    $stmt = $pdo -> query($sql);
    $results = $stmt -> fetchAll();
    if ($results !== false) {
        foreach ($results as $row) {
        $table .= 'ID: '.$row['id'].' ユーザーネーム: '.$row['username'].' パスワード: '.$row['password']."<br>";
    } else {
        $table = '';
    }
    
}*/

//テスト用
error_reporting(E_ALL);     // すべてのエラーを表示する
ini_set('display_errors', 1);   // エラーレポートを有効にする

?>

<!DOCTYPE html>
<html lang="ja">

    <head>
        <meta charset="utf-8">
        <title>ログイン</title>
        <link rel="stylesheet" href="style.css">
    </head>

    <body>
        <header>
            <h1>交換日記</h1>
        </header>
        <main>
            <p class="right">
                <form action="home.php" method="POST">
                    <input type="submit" name="back" value="ホームへ戻る">
                </form>
            </p>

            <h2>ログイン</h2>

            <p>
                <form action="" method="POST">
                    <input type="text" name="username" placeholder="ユーザーネーム" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES) ?>"> <br>
                    <input type="password" name="password" placeholder="パスワード"> <br>
                    <input type="submit" name="login" value="ログイン"> <br>
                </form>
            </p>
            <div class="error"> <?= $error_message ?> </div>
        </main>
    </body>
</html>