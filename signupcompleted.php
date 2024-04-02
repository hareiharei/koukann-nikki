<?php
session_start();
$username = $_SESSION['username'];

if (isset($_POST['back'])) {
    $_SESSION = array();
    session_destroy();
    header("Location: home.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <title>新規登録完了</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <h1>交換日記</h1>
    </header>
    <main>
        <div class="right">
            <p>
            <form action="home.php" method="POST">
                <input type="submit" name="back" value="ホームへ戻る">
            </form>
            </p>
        </div>

        <h2>新規登録が完了しました</h2>

        <p>
            ユーザーネーム: <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?> <br>
            パスワード: この画面では表示されません
        </p>
    </main>

</body>

</html>