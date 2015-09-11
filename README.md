# bitbucket-slack
BitbucketのPull Request、コミット、コメントをSlackにPostします

##コンセプト
1ファイルのわかりやすい構成としています。  
最低限の実装なので、ご自由に改編して使ってください。  
PHPの経験が少ない方でも改編して使って頂けるように極力シンプルに作ったつもりです。  

##使い方

###前提
slackのチャンネル名は #test

WebAPIのTokenは取得済みである

参考：
tokenの取得方法は割愛しますが以下のURLから取得できます。  
https://api.slack.com/web

###最低限の設定

1.PHPが動くサーバーにファイルをおきます。  
  
http://example.com/bitbucket2slack.php としてアクセスできる前提とします。  
  
2.設置したファイル内のSLACK_API_TOKENを適切なtokenに書き換えます。  

Bitbuketの通知を行いたいリポジトリにて設定Webhooksの設定を行ないます。
  
Settings-> Webhooks
  
URL設定欄にパラメータchannelを追加してURLを設定します。  
(チャンネル名の#はつけません)  
e.g.
http://example.com/bitbucket2slack.php?channel=test

Triggers->Choose from a full list of triggers
のチェックをいれ、
Pull Requestの必要箇所にチェックを入れます。

###その他
アイコンは適宜変えてください。
Webhooksのリクエストに、ユーザーのアイコン情報が含まれますので、
それらを使うように改変して頂くのもよいかと思います。

###動かない場合
USE_SYSTEM_LOG,USE_DISPLAY_LOG,SAVE_REQUEST_DATAの各項目をtrueに設定することで、
動作検証の手助けができます。
USE_SYSTEM_LOGを有効にした場合には、/var/log/httpd/error.logなどに出力されます。  
※Webサーバーの設定により異なりますのでパスは適宜読み替えてください
