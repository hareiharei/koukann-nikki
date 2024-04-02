<?php
session_start();
$my_id = $_SESSION['id'];
$my_username = $_SESSION['username'];

//変数の初期設定
//$my_id = 1;  //テスト用
//$my_username = "username1";  //テスト用
$friends_str = "";
$friends_list = [];
//$verified_friendname = "";
$error_message_add = "";
$error_message_select = "";
$error_message = "";
//$no_friendname = "";
$friends_tablename = "friends_{$my_id}";

//データベース接続
include "database_connect.php";
dbc();
$error_message_add = $error_message;

showFriends();

//友達追加
if (isset($_POST['add_friend'])) {  //追加ボタンが押されたら
    if (!empty($_POST['added_friendname'])) {  //友達のユーザーネームが入力されているか確認
        $added_friendname = $_POST['added_friendname'];

        //入力されたユーザーネームがデータベースにあるか確認
        $sql = "SELECT * FROM users WHERE username=:username";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $added_friendname, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result !== false) {
            $friend_id = $result['id'];
            $verified_friendname = $result['username'];
        } else {
            $friend_id = '';
            $verified_friendname = '';
        }


        //入力されたユーザーネームが既に友達として追加されていないか確認
        $sql = "SELECT * FROM {$friends_tablename} WHERE friend_name=:friend_name";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':friend_name', $added_friendname, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result !== false) {
            $no_friendname = $result['friend_name'];
        } else {
            $no_friendname = '';
        }

        //データベースに入力されたユーザーネームが存在した場合
        if (!empty($verified_friendname) && $verified_friendname != $my_username && empty($no_friendname)) {
            addFriend($my_id, $friend_id, $verified_friendname);  //自分のデータベースに追加
            addFriend($friend_id, $my_id, $my_username);  //相手のデータベースに追加
            showFriends();  //友達の表示を更新
            $error_message_add = "{$verified_friendname}が友達に追加されました";

            //値が小さい方のidを1番目、大きい方のidを2番目にする
            if ($my_id < $friend_id) {
                $id_1 = $my_id;
                $id_2 = $friend_id;
            } else {  // $friend_id < $my_id
                $id_1 = $friend_id;
                $id_2 = $my_id;
            }

            $journal_tablename = "journals_{$id_1}_{$id_2}";

            //交換日記のテーブル作成
            $sql = "CREATE TABLE IF NOT EXISTS {$journal_tablename} "
                . "("
                . "id INT AUTO_INCREMENT PRIMARY KEY,"
                . "sent INT,"
                . "time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,"
                . "weather VARCHAR(10),"
                . "happy TEXT,"
                . "sad TEXT,"
                . "food VARCHAR(30),"
                . "music VARCHAR(30),"
                . "answer TEXT,"
                . "friend_question TEXT,"
                . "my_question TEXT,"
                . "photo_name TEXT,"
                . "photo_path TEXT"
                . ");";
            $pdo->query($sql);
        } elseif ($verified_friendname == $my_username) {
            $error_message_add = "自分を友達として追加することはできません";
        } elseif (!empty($no_friendname)) {
            $error_message_add = "このユーザーは既に友達として登録されています";
        } else {  //データベースに入力されたユーザーネームが存在しなかった場合
            $error_message_add = "ユーザーが存在しません";
        }
    } else {  //友達のユーザーネームが入力されていなかった場合
        $error_message_add = "友達のユーザーネームが入力されていません";
    }
}

//友達選択
if (isset($_POST['select_friend'])) {  //選択ボタンが押されたら
    if (!empty($_POST['selected_friendname'])) {  //友達が選択されているか確認
        //友達との交換日記ページへ移動
        $selected_friendname = $_POST['selected_friendname'];

        $sql = "SELECT * FROM {$friends_tablename} WHERE friend_name=:friend_name";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam('friend_name', $selected_friendname, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result !== false) {
            $friend_id = $result['friend_id'];
        } else {
            $friend_id = '';
        }

        $_SESSION['my_id'] = $my_id;
        $_SESSION['friend_id'] = $friend_id;
        $_SESSION['my_username'] = $my_username;
        $_SESSION['friend_username'] = $selected_friendname;

        header("Location: journal.php");
    } else {  //友達が選択されていなかった場合
        $error_message_select = "友達が選択されていません";
    }
}

//ログアウト
if (isset($_POST['logout'])) {  //ログアウトボタンが押されたら
    session_destroy();
}

//友達の更新
function showFriends()
{
    global $friends_tablename;
    global $pdo;
    global $friends_str;
    global $friends_list;
    $sql = "SELECT * FROM " . $friends_tablename;
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();
    if ($results !== false) {
        foreach ($results as $row) {
            $friends_str .= $row['friend_name'] . '<br>';
            $friends_list[] = $row['friend_name'];
        }
    } else {
        $friends_str = '';
        $friends_list[] = [];
    }
}

//友達をデータベースに追加
function addFriend($my_id, $friend_id, $friend_username)
{
    global $pdo;
    $friends_tablename = "friends_{$my_id}";
    $sql = "INSERT INTO {$friends_tablename} (friend_id, friend_name) VALUES (:friend_id, :friend_name)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':friend_id', $friend_id, PDO::PARAM_INT);
    $stmt->bindParam(':friend_name', $friend_username, PDO::PARAM_STR);
    $stmt->execute();
}

// すべてのエラーを表示する
error_reporting(E_ALL);
// エラーレポートを有効にする
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <title><?= $my_username ?>さんのマイページ</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <h1>交換日記</h1>
    </header>
    <main>
        <div class="right">
            <form action="home.php" method="POST">
                <input type="submit" name="logout" value="ログアウト">
            </form>
        </div>

        <h2><?= $my_username ?>さんのマイページ</h2>


        <h3> <label for="added_friendname">新しい友達を追加する</label> </h3>
        <p>
        <form action="" method="POST">
            <input type="text" name="added_friendname" id="added_friendname" placeholder="友達のユーザーネーム">
            <input type="submit" name="add_friend" value="追加">
        </form>
        </p>
        <div class="error"> <?= $error_message_add ?> </div>




        <h3> <label for="selected_friendname">友達と交換日記をする</label> </h3>
        <p>
        <form action="" method="POST">
            <select name="selected_friendname" id="selected_friendname">
                <option value="" disabled selected>友達のユーザーネーム</option>
                <?php
                foreach ($friends_list as $friend) {
                    $selected = (isset($selected_friendname) && $selected_friendname == $friend) ? ' selected' : '';
                    echo "<option value=\"$friend\"$selected>$friend</option>";
                }
                ?>
            </select>
            <input type="submit" name="select_friend" value="選択">
        </form>
        </p>
        <div class="error"> <?= $error_message_select ?> </div>

        <h3>友達一覧</h3>
        <p> <?= $friends_str . '<br>' ?> </p>

    </main>
</body>
</html>