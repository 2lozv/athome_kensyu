<?php
///////////////////////////////////////////////////////////////////////
//基本処理

//データベースへのログイン情報
$dsn = "mysql:host=localhost;dbname=memo;charset=utf8"; //db作ったら書く
$user = "testuser";
$pass = "testpass";

//受け取ったデータを処理する
$origin = []; //ここに処理前のデータが入る
if(isset($_POST)){
    $origin += $_POST; //$originに処理前のGETのデータを入れる
}

//文字コードとHTMLエンティティズの処理を繰り返し行う
foreach($origin as $key => $value){
    //文字コードを処理
    $mb_code = mb_detect_encoding($value); //mb_detect_encodingで()内の現在の文字コードを調べる
    $value = mb_convert_encoding($value, "utf-8", $mb_code); //mb_convert_encodingで文字コードを変更。(文字コード変更する値, 変換する文字コード, 現在の文字コード)

    //HTMLエンティティズ処理
    $value = htmlentities($value, ENT_QUOTES);

    //処理が終わったデータを、$inputに入れなおす
    $input[$key] = $value;
}

//データベースへ接続
try{
    $dbh = new PDO($dsn,$user,$pass);
    if(isset($input["mode"]) && $input["mode"] == "register"){
        register(); //登録処理
    }else if(isset($input["mode"]) && $input["mode"] == "delete"){
        delete();
    }
    display(); //表示処理
}catch(PDOException $e){
    echo "接続失敗";
    echo "エラー内容：".$e -> getMessage();
}

////////////////////////////////////////////////////
//関数(機能)を別々に作っていきます

function register(){ //register(登録)という関数を作成
    //グローバル宣言をして、基本処理で作成した変数を、関数内で使用可能にする
    global $dbh, $input;
    //stockテーブルの、nameとpriceの値に、入力された商品名と値段を登録
    //SQl文を用意(?でプレイスホルダーに)
    $sql=<<<sql
    insert into memotable (memo) values(?);
sql;
    //prepare()メソッドを使って、SQLの実行結果を$stmtオブジェクトに保留
    $stmt = $dbh -> prepare($sql);

    //プレイスホルダー（値が未確定だったところに値を紐づけ）
    $stmt -> bindParam(1, $input["memo"]);
    
    //保留していたSQLを実行
    $stmt -> execute();

}

function display(){ //display(画面に表示)という関数を作成
    //基本処理で定義した変数を使えるようにする
    global $dbh, $input;

    //SQL文を用意
    $sql=<<<sql
    select * from memotable where flag = 1 order by id desc;
sql;
    $stmt = $dbh -> prepare($sql);
    $stmt -> execute();

    //$blockがテキストかつ中身がないことを定義する
    $block = "";

    //テンプレートファイルの読み込み
    $fh = fopen("memoinsert.tmpl", "r+"); //読み込みモードで。insert.tmlを開く
    $fs = filesize("memoinsert.tmpl"); //ファイルサイズを調べる(のちのfread関数で使う)
    $insert_tmpl = fread($fh, $fs); //ファイルの読み込みを行う

    while($row = $stmt -> fetch()){ //レコードを1行ずつ繰り返し$blockに詰め込む
        //差し込み用テンプレートを初期化する
        $insert = $insert_tmpl;

        //DBの値を、PHPで使用する値として、変数に入れなおす
        $date = $row["date"];
        $memo = $row["memo"];
        $id = $row["id"];

        //テンプレートファイルの文字を置き換える
        $insert = str_replace("!date!", $date, $insert);
        $insert = str_replace("!memo!", $memo, $insert);
        $insert = str_replace("!id!", $id, $insert);

        //stock.htmlに差し込む変数に格納する
        $block .= $insert; //ループするたびに、insert_tmplの値を追記していく
    }

    //stock.htmlの!block!に$blockを置き換える
    $fh = fopen("memo2.html", "r+");
    $fs = filesize("memo2.html");
    $top = fread($fh, $fs); //80~82行目でやったことと同じ
    
    //$top(stock.htmlのデータ)の!block!に$blockを置き換える
    $top = str_replace("!block!", $block, $top);

    //全てを差し替えたデータを、ブラウザに表示
    echo $top;
}

function delete(){
    global $dbh;
    global $input;

    //SQl文を用意
    $sql=<<<sql
        update memotable set flag = 0 where id = ?;
    sql;

    $stmt = $dbh -> prepare($sql);

    $stmt -> bindParam(1, $input["id"]);

    $stmt -> execute();

}