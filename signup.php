<?php
session_start();

//データベース接続
include "database_connect.php";
dbc();

        
//テーブル作成（初めの1回だけ）
$sql = 'CREATE TABLE IF NOT EXISTS users'
.'('
.'id INT AUTO_INCREMENT PRIMARY KEY,'
.'username VARCHAR(20),'
.'password VARCHAR(100)'
.');';
$stmt = $pdo -> query($sql);
        
//変数の初期設定
$error_message = "";
        
//新規登録
if (isset($_POST['signup'])) {  //登録ボタンが押されたら
    if (!empty($_POST['username'])) {  //ユーザーネームが入力されていることを確認
        if (!empty($_POST['password'])) {  //パスワードが入力されていることを確認
            //入力されたユーザーネームとパスワードを取得、パスワードのハッシュ化
            $input_username = $_POST['username'];
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            //入力されたユーザーネームがデータベースに存在するか確認
            $sql = 'SELECT * FROM users WHERE username=:username';
            $stmt = $pdo->prepare($sql);
            $stmt -> bindParam(':username', $input_username, PDO::PARAM_STR);
            $stmt -> execute();
            $result = $stmt -> fetch(PDO::FETCH_ASSOC);
            if ($result !== false) {
                $database_username = $result['username'];
            } else {
                $database_username = '';
            }
            
            if (empty($database_username) && !is_numeric($input_username)) {    //入力されたユーザーネームが既に登録されていないことを確認
                //データベースに新規登録
                $sql = 'INSERT INTO users (username, password) VALUES (:username, :password)';
                $stmt = $pdo -> prepare($sql);
                $stmt -> bindParam(':username', $input_username, PDO::PARAM_STR);
                $stmt -> bindParam(':password', $hashed_password, PDO::PARAM_STR);
                $stmt -> execute();
                
                //追加したユーザーのIDを取得
                $sql = "SELECT * FROM users WHERE username=:username";
                $stmt = $pdo -> prepare($sql);
                $stmt -> bindParam(':username', $input_username, PDO::PARAM_STR);
                $stmt -> execute();
                $result = $stmt -> fetch(PDO::FETCH_ASSOC);
                if ($result !== false) {
                    $my_id = $result['id'];
                } else {
                    $error_message = "新規登録中にエラーが発生しました";
                    exit;
                }
                
                //自分の友達リストのデータベース作成
                $friends_tablename = "friends_{$my_id}";
                $sql = "CREATE TABLE IF NOT EXISTS {$friends_tablename} "
                ."("
                ."friend_id INT,"
                ."friend_name VARCHAR(20)"
                .");";
                $pdo -> query($sql);
                
                //新規登録完了画面に移動
                $_SESSION['username'] = $input_username;
                header("Location: signupcompleted.php");
                exit;
                
            } elseif (is_numeric($input_username)) { 
                $error_message = "数字のみで構成されるユーザーネームは登録できません"; 
            } else {
                $error_message = "このユーザーネームは既に登録されています";
            }
        } else { $error_message = "パスワードが入力されていません"; }
    } elseif (!empty($_POST['password'])) { //ユーザーネームは入力されていないが、パスワードは入力されている場合
        $error_message = "ユーザーネームが入力されていません"; 
    } else { //ユーザーネームもパスワードも入力されていない場合
        $error_message = "ユーザーネームとパスワードが入力されていません"; 
    }
    //今後の課題: ユーザーネーム、パスワードのバリデーション強化をしたい
}

?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>新規登録</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <header>
            <h1>交換日記</h1>
        </header>
        <div class="right">
            <p>
                <form action="home.php" method="POST">
                    <input type="submit" name="back" value="ホームへ戻る">
                </form>
            </p>
        </div>
        
        <h2>新規登録</h2>
        
        <p>
            <form action="" method="POST">
                <input type="text" name="username" placeholder="ユーザーネーム" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES) ?>"><br>
                <input type="password" name="password" placeholder="パスワード"><br>
                <input type="submit" name="signup" value="登録"><br>
            </form>
        </p>
        <div class="error"> <?= $error_message ?> </div>
    </body>
</html>