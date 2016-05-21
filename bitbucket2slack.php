<?php
/**
 * BitbucketのPullRequestをSlackに通知します。
 *Lolipop用 パーミッションは 644
 */

// SlackのAPI Tokenを設定
define('SLACK_API_TOKEN', 'xoxp-0000000-0000000000-000000000-0000000000');

// アイコンの画像を指定(適宜変更してください)
define('ICON_URL','https://secure.gravatar.com/avatar/72e4031227e1191787447be1767c7ba8.jpg?s=48&d=https%3A%2F%2Fa.slack-edge.com%2F0180%2Fimg%2Favatars%2Fava_0001-48.png');

// BotName(適宜変更してください)
define('BOT_NAME', 'Bitbucket');

//----------------------------------------------------------------------------------------------------------------------

// 動作ログを書き込み
define('USE_SYSTEM_LOG', true);

// 画面に結果を表示
define('USE_DISPLAY_LOG', true);

// 主にデバッグ用
// 有効にする場合は このファイルと同じ階層に tmpフォルダを作成し、適切なパーミッションを設定してください
define('SAVE_REQUEST_DATA', false);

//----------------------------------------------------------------------------------------------------------------------

// API URL
define('SLACK_API_URL',"https://slack.com/api/chat.postMessage?token=%%TOKEN%%&channel=%%CHANNEL%%&text=%%TEXT%%&username=%%USERNAME%%&icon_url=%%ICON_URL%%&pretty=1");

//----------------------------------------------------------------------------------------------------------------------

//systemLog("bitbucket2slack");

$channel = "#".$_GET['channel'];

//systemLog("request channel:".$channel);

if(!$channel){
    $message = 'channel is not set.';
     systemLog($message);
    //error($message);
    exit;
}

//-------------------------------------------------------------------

$inputPath = 'php://input';
//systemLog("inputPath:".$inputPath);
// 動作検証様に取得したファイルを使用する場合はここを設定してください
//$inputPath = "./tmp/request_xxxxxxxxxxxxxx.txt";
//systemLog("file_get_contents('php://input')=".file_get_contents('php://input'));

$rawJson = file_get_contents($inputPath);
//systemLog("rawJson:".$rawJson);

if(!$rawJson){
    $message = 'request body is not set. request channel:'.$channel;
    systemLog("message:".$message);
    //error($message);
    //exit;
}

// リクエストを保存
saveRequest($rawJson);

$aryJson = json_decode($rawJson, true);
//var_dump($aryJson);

// リクエストされた内容を整形
$message = RequestDataFormatter($aryJson);

if(!$message){

    $message = 'request is not supported. request channel:'.$channel. " request data:".$file;
    systemLog("message:".$message);
    //error($message);
    exit;
}

systemLog($message);

// slackにメッセージ送信
$messageData = SlackApiSendMessage($message, $channel);

if($messageData['ok'] !== true){
    // メッセージ送信でエラーの場合
    $message = 'send message_error:'."\n".$message."\n".var_export($messageData, true);
    systemLog($message);
    //error($message);
    exit;
}

// 正常終了
$msg = "Finish. Status:OK";
$processEnd = true;

systemLog($msg, $processEnd);

print "OK\n";

exit;

//----------------------------------------------------------------------------------------------------------------------

/**
 * slackAPI連携
 * メッセージの送信
 * @param $message
 * @param $channel
 * @return bool
 */

function SlackApiSendMessage($message, $channel){

    $channel  = rawurlencode($channel);
    $message  = rawurlencode($message);
    $username = rawurlencode(BOT_NAME);

    $iconUrl = rawurlencode(ICON_URL);

    $url = SLACK_API_URL;
    $url = str_replace('%%TEXT%%'    , $message, $url);
    $url = str_replace('%%TOKEN%%'   , SLACK_API_TOKEN , $url);
    $url = str_replace('%%CHANNEL%%' , $channel, $url);
    $url = str_replace('%%USERNAME%%', $username, $url);
    $url = str_replace('%%ICON_URL%%', $iconUrl ,$url);

    $result = file_get_contents($url);

    $jsonData =json_decode($result, true);

    systemLog("send message result");
    systemLog($result);

    if($jsonData['ok'] != true){
        $jsonData['CHANNEL'] = $channel;
    }

    return $jsonData;

}

/**
 * エラー時の処理
 * @param $message
 * @param $status
 */
function error($message, $status=403){

    systemLog($message);

    header('HTTP', true, $status);

    $msg = "Finish. Status:ERROR";
    $processEnd = true;

    systemLog($msg, $processEnd);

    print "ERROR\n";

    exit;

}

/**
 * ログ出力
 * @param $msg
 * @param bool|false $processEnd
 */
function systemLog($msg, $processEnd=false){

    static $init = false;

    if(!$init){
        // キャッシュ用
        ob_start();
        $init = true;
    }

    if(USE_SYSTEM_LOG){
        error_log($msg);
    }

    if(USE_DISPLAY_LOG){
        print $msg;
        print "<hr />";
    }

    if($processEnd){

        // キャッシュしたメッセージを表示
        ob_end_flush();

        return;
    } else{

        return;
    }

}

/**
 * 受け取ったリクエストをjson形式で保存
 * @param $rawJson
 */
function saveRequest($rawJson){

    if(SAVE_REQUEST_DATA){
        // リクエストを保存
        $file = "./tmp/request_".date("YmdHis");
        file_put_contents($file, $rawJson);
    }

    return;
}

/**
 * 受け取ったjsonからメッセージを切り出して整形
 * @param $jsonData
 * @return bool|string
 */
function RequestDataFormatter($jsonData)
{

    if(!isset($jsonData['repository'])){
        return false;
    }

    $repositoryName = $jsonData['repository']['full_name'];

    systemLog("repositoryName: {$repositoryName}");

    if(isset($jsonData['comment'])){
        // コメント

        $content  = $jsonData['comment']['content']['raw'];
        $link   = $jsonData['comment']['links']['html']['href'];

        $user = $jsonData['comment']['user']['display_name'];

        $message  = "[{$repositoryName}] Commented by {$user}\n";
        $message .= "{$content}\n";
        $message .= "{$link}\n";

        return $message;

    } elseif(isset($jsonData['pullrequest'])){

        //var_dump($jsonData['pullrequest']);

        $author = $jsonData['pullrequest']['author']['username'];
        $link   = $jsonData['pullrequest']['links']['html']['href'];
        $title  = $jsonData['pullrequest']['title'];
        $state  = $jsonData['pullrequest']['state'];

        $message  = "[{$repositoryName}] Pull request {$state} by {$author}\n";
        $message .= "{$title}\n";
        $message .= "{$link}\n";

        return $message;

    } elseif(isset($jsonData['push'])){


        $title = $jsonData['push']['changes'][0]['new']['target']['message'];

        $branch = $jsonData['push']['changes'][0]['new']['name'];

        $author = $jsonData['actor']['username'];

        $link = $jsonData['push']['changes'][0]['links']['html']['href'];

        $hash = $jsonData['push']['changes'][0]['new']['target']['hash'];
        $hash = substr($hash, 0, 6);

        $message  = "[{$repositoryName}] <{$link}|New commit {$hash}>\n";
        $message .= "branch: {$branch}  by {$author} \n";
        $message .= "{$title}\n";

        return $message;

    } else{

        $msg = "Unknown type.";
        systemLog($msg);

        return false;
    }

}

ini_set("display_errors", On);
error_reporting(E_ALL);
?>
