<?php
session_start();
$my_id = $_SESSION['my_id'];
$friend_id = $_SESSION['friend_id'];
$my_username = $_SESSION['my_username'];
$friend_username = $_SESSION['friend_username'];

//変数の初期設定
$error_message = "";
$message = "";
$journals = "";
$edit_id = 0;
$editting = 0;

if ($my_id < $friend_id) {
    $id_1 = $my_id;
    $id_2 = $friend_id;
} else {  // $friend_id < $my_id
    $id_1 = $friend_id;
    $id_2 = $my_id;
}

$journal_tablename = "journals_{$id_1}_{$id_2}";

//データベース接続
include "database_connect.php";
dbc();

//最後に送信された交換日記を読み込む
$sql = "SELECT * FROM {$journal_tablename} ORDER BY id DESC LIMIT 1";
$stmt = $pdo->query($sql);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result !== false && !empty($result['sent']) && !empty($result['my_question'])) {
    $last_sent = $result['sent'];
    $friend_question = $result['my_question'];
} else {
    //一番初めに送る交換日記用
    $last_sent = $friend_id;
    $friend_question = "初めの交換日記なので友達からの質問はありません";
}

showJournals();
getId();

if (isset($_POST['send'])) {         //送信ボタンが押されたら
    if ($last_sent == $friend_id) {  //最後に送られた交換日記が相手からのものであることを確認
        //すべての値が入力されていることを確認
        if (
            !empty($_POST['weather']) && !empty($_POST['happy']) && !empty($_POST['sad']) && !empty($_POST['food']) &&
            !empty($_POST['music']) && !empty($_POST['answer']) && !empty($_POST['my_question'])
        ) {
            //テキストデータの処理
            $editting = $_POST['editting'];
            $time = date("Y/m/d H:i:s");
            $weather = $_POST['weather'];
            $happy = $_POST['happy'];
            $sad = $_POST['sad'];
            $food = $_POST['food'];
            $music = $_POST['music'];
            $answer = $_POST['answer'];
            $my_question = $_POST['my_question'];
            $edit_id = 0;

            //新規作成モード
            if ($editting == 0) {
                //データベースに入力内容を追加
                $sql =   "INSERT INTO {$journal_tablename} (sent, time, weather, happy, sad, food, music, answer, friend_question, my_question) "
                    . "VALUES (:sent, :time, :weather, :happy, :sad, :food, :music, :answer, :friend_question, :my_question)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':sent', $my_id, PDO::PARAM_INT);
                $stmt->bindParam(':time', $time, PDO::PARAM_STR);
                $stmt->bindParam(':weather', $weather, PDO::PARAM_STR);
                $stmt->bindParam(':happy', $happy, PDO::PARAM_STR);
                $stmt->bindParam(':sad', $sad, PDO::PARAM_STR);
                $stmt->bindParam(':food', $food, PDO::PARAM_STR);
                $stmt->bindParam(':music', $music, PDO::PARAM_STR);
                $stmt->bindParam(':answer', $answer, PDO::PARAM_STR);
                $stmt->bindParam(':friend_question', $friend_question, PDO::PARAM_STR);
                $stmt->bindParam(':my_question', $my_question, PDO::PARAM_STR);
                $stmt->execute();
                //新規作成完了
                $message = "新たに日記を送信しました";

                //編集モード
            } else {  // $editting != 0
                // データベースを更新
                $sql = "UPDATE {$journal_tablename} SET "
                    . "time=:time, weather=:weather, happy=:happy, sad=:sad, food=:food, music=:music, answer=:answer, my_question=:my_question "
                    . "WHERE id=:id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':time', $time, PDO::PARAM_STR);
                $stmt->bindParam(':weather', $weather, PDO::PARAM_STR);
                $stmt->bindParam(':happy', $happy, PDO::PARAM_STR);
                $stmt->bindParam(':sad', $sad, PDO::PARAM_STR);
                $stmt->bindParam(':food', $food, PDO::PARAM_STR);
                $stmt->bindParam(':music', $music, PDO::PARAM_STR);
                $stmt->bindParam(':answer', $answer, PDO::PARAM_STR);
                $stmt->bindParam(':my_question', $my_question, PDO::PARAM_STR);
                $stmt->bindParam(':id', $editting, $PDO::PARAM_INT);
                $stmt->execute();
                //編集完了 変数を再度初期化
                $edit_id = 0;
                $editting = 0;
                $message = "{$editting}番の日記を編集しました";
            }

            //今後の課題: 写真アップロード機能の実装
            
            //新規作成・編集完了
            showJournals();
            getId();
        } else {
            $error_message = "入力されていない欄があります";
        }
    } else {
        $error_message = "相手から交換日記が送られるまで自分から送ることはできません";
    }

    //交換日記の編集
} elseif (isset($_POST['edit'])) {  //編集ボタンが押されたら
    if (!empty($_POST['edit_id'])) {  //編集する日記の番号が選択されたら
        $edit_id = $_POST['edit_id'];
        $sql = "SELECT * FROM {$journal_tablename} WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result !== false) {
            $edit_weather = $result['weather'];
            $edit_happy = $result['happy'];
            $edit_sad = $result['sad'];
            $edit_food = $result['food'];
            $edit_music = $result['music'];
            $edit_answer = $result['answer'];
            $edit_question = $result['my_question'];
        } else {
            $edit_weather = '';
            $edit_happy = '';
            $edit_sad = '';
            $edit_food = '';
            $edit_music = '';
            $edit_answer = '';
            $edit_question = '';
        }
                                    
        //今後の課題: 画像の変更の実装

        $message = "{$edit_id}番の日記を編集中です";
    }

//交換日記の削除
} elseif (isset($_POST['delete'])) {  //削除ボタンが押されたら
    if (!empty($_POST['delete_id'])) {  //削除する日記の番号が選択されたら
        $delete_id = $_POST['delete_id'];
        $sql = "DELETE FROM {$journal_tablename} WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        $stmt->execute();
        //削除完了
        showJournals();
        getId();
        $message = "{$delete_id}番の日記を削除しました";
    }
}

//ログアウト
if (isset($_POST['logout'])) {
    session_destroy();
}

//マイページへ戻る
if (isset($_POST['back'])) {
    $_SESSION['id'] = $my_id;
    $_SESSION['username'] = $my_username;
    header("Location: mypage.php");
}

//交換日記の表示を更新 新しい順に表示
function showJournals()
{
    global $journals;
    global $pdo;
    global $journal_tablename;
    global $my_id;
    global $friend_id;
    global $my_username;
    global $friend_username;

    $journals = "";
    $sql = "SELECT * FROM {$journal_tablename}";
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();
    if ($results !== false) {
        $results = array_reverse($results);
        foreach ($results as $result) {
            $id = $result['id'];
            $sent = $result['sent'];
            $time = $result['time'];
            $weather = $result['weather'];
            $happy = $result['happy'];
            $sad = $result['sad'];
            $food = $result['food'];
            $music = $result['music'];
            $answer = $result['answer'];
            $friend_question = $result['friend_question'];
            $my_question = $result['my_question'];

            if ($sent == $my_id) {
                $username = $my_username;
            } elseif ($sent == $friend_id) {
                $username = $friend_username;
            }

            $journals .= "{$id}: {$username}の日記 ({$time})<br>"
                . "　今日の天気: {$weather}<br>"
                . "　今日の嬉しかったこと: {$happy}<br>"
                . "　今日の悲しかったこと: {$sad}<br>"
                . "　今日食べたごはん: {$food}<br>"
                . "　今日聴いた音楽: {$music}<br>"
                . "　友達からの質問({$friend_question})への答え: {$answer}<br>"
                . "　友達への質問: {$my_question}<br><br>";
        }
    } else {
        $journals = '';
    }
}

//編集・削除可能な交換日記の番号を取得
function getId()
{
    global $pdo;
    global $journal_tablename;
    global $my_id;
    global $results_id;
    $sql = "SELECT * FROM {$journal_tablename} WHERE sent=:sent";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':sent', $my_id, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($results !== false) {
        $results_id = $results['id'];
    } else {
        $results_id = '';
    }
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <title><?= $my_username ?>さんと<?= $friend_username ?>さんの交換日記</title>
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
            <form action="mypage.php" method="POST">
                <input type="submit" name="back" value="マイページへ戻る">
            </form> <br>
        </div>

        <h2><?= $my_username ?>さんと<?= $friend_username ?>さんの交換日記</h2>

        <div class="journal">
            <section>
                <h3>交換日記を送る</h3>
                <p>
                <form action="" method="POST" enctype="multipart/form-data">
                    <dl>
                        <dt> <label for="weather">今日の天気:</label> </dt>
                        <dd>
                            <input type="radio" name="weather" id="weather" value="晴れ">晴れ
                            <input type="radio" name="weather" id="weather" value="曇り">曇り
                            <input type="radio" name="weather" id="weather" value="雨">雨
                            <input type="radio" name="weather" id="weather" value="雪">雪
                        </dd>
                        <dt> <label for="happy">今日の嬉しかったこと</label> </dt>
                        <dd> <textarea name="happy" id="happy" value="<?= (!empty($edit_id)) ? "$edit_happy" : "" ?>" rows="4" cols="50"></textarea> </dd>
                        <dt> <label for="sad">今日の悲しかったこと</label> </dt>
                        <dd> <textarea name="sad" id="sad" value="<?= (!empty($edit_id)) ? "$edit_sad" : "" ?>" rows="4" cols="50"></textarea> </dd>
                        <dt> <label for="food">今日食べたごはん</label> </dt>
                        <dd> <input type="text" name="food" id="food" value="<?= (!empty($edit_id)) ? "$edit_food" : "" ?>"></dd>
                        <dt> <label for="music">今日聴いた音楽</label> </dt>
                        <dd> <input type="text" name="music" id="music" value="<?= (!empty($edit_id)) ? "$edit_music" : "" ?>"> </dd>
                        <dt> <label for="answer">友達からの質問(<?= $friend_question ?>)の答え</label> </dt>
                        <dd> <textarea name="answer" id="answer" value="<?= (!empty($edit_id)) ? "$edit_answer" : "" ?>" rows="4" cols="50"></textarea> </dd>
                        <dt> <label for="my_question">友達への質問</label> </dt>
                        <dd> <input type="text" name="my_question" id="my_question" value="<?= (!empty($edit_id)) ? "$edit_question" : "" ?>"> </dd>
                        <dt> <label for="photo">今日の写真</label> </dt>
                        <dd> <input type="file" name="photo" id="photo"> </dd>
                    </dl>
                    <input type="hidden" name="editting" value="<?= $edit_id ?>">
                    <input type="submit" name="send" value="送信">
                </form>
                </p>
                <div class="error"> <?= $error_message ?> </div>
            </section>
            <h3>交換日記を編集/削除する</h3>
            <p>
            <form action="" method="POST">
                <select name="edit_id">
                    <option value="" disabled selected>編集する交換日記の番号を選択</option>
                    <?php
                    foreach ($results_id as $id) {
                        $selected = (isset($edit_id) && $edit_id == $id) ? ' selected' : '';
                        echo "<option value=\"$id\"$selected>$id</option>";
                    }
                    ?>
                </select>
                <input type="submit" name="edit" value="編集">
            </form>
            <form action="" method="POST">
                <select name="delete_id">
                    <option value="" disabled selected>削除する交換日記の番号を選択</option>
                    <?php
                    foreach ($results_id as $id) {
                        $selected = (isset($delete_id) && $delete_id == $id) ? ' selected' : '';
                        echo "<option value=\"$id\"$selected>$id</option>";
                    }
                    ?>
                </select>
                <input type="submit" name="delete" value="編集">
            </form>
            </p>

            <h3>今までの交換日記</h3>
            <p> <?= $journals ?> </p>
        </div>
    </main>
</body>

</html>
