# ● php mailformのライセンス
php mailform はフリーソフトです。（MITライセンス）<br>
MITライセンスのもと、どなたでも自由に仕様、改変、再配布することができます。<br>


<br>
# ● php mailformの設置方法

mailform_version_x.xx.zip を解凍ソフトで解凍すると「php_mailform」フォルダが生成されます。<br>
**1.**「php_mailform」フォルダごとお使いのWEBサーバにFTPアップロードします。<br>
**2.** ファイル「php_mailform/phplib/mailform/config.yml」のパーミッションを <b>600</b> にします。<br>
**3.** ブラウザに　http://設置したサーバー名/php_mailform/php_mailform.php と入力してアクセスします。<br>
<br>


<br>
# ● php mailformの設定方法

ファイルをアップロードしただけでは使用できません。設定ファイルを開いて適宜設定を変更して下さい。<br>
<b>設定ファイル「php_mailform/phplib/mailform/config.yml」</b><br>
**1.** config.ymlの【メールの設定】項目を設定して下さい。<br>
**2.** config.ymlの【フォームの設定】項目を設定して下さい。<br>
<br>
設定が完了した config.yml はサーバ上のものと置き換えて下さい。



<br>
# ● フォーム項目の設定方法（ config.yml フォームの設定）

フォームの設定は以下のようになっています。適宜書き換えて下さい。不要な入力項目は項目自体を削除して下さい。
```
  name :
    title_mail : お名前
    title_html : お名前 ※
    placeholder: 問い合わせ　太郎
    input_type : text # text, number, email, tel, password, select, checkbox, textarea, radio, file から選択。
    validate   :
      NOT_BLANK  : お名前を入力してください。

  postal :
    title_mail : 郵便番号
    title_html : 郵便番号
    placeholder: 123-4567
    class      : p-postal-code
    input_type : number # text, number, email, tel, password, select, checkbox, textarea, radio, file から選択。
    validate   :
      REGEX,^[0-9\-０-９−―-]+$ : 郵便番号を正しく入力してください。

  address :
    title_mail : 住所
    title_html : 住所
    placeholder: 住所
    class      : p-region p-locality p-street-address p-extended-address
    input_type : text # text, number, email, tel, password, select, checkbox, textarea, radio, file から選択。
    validate   :
```

###● フォーム項目を必須項目にするには下記を追加して下さい。
```
    validate   :
      NOT_BLANK  : お名前を入力してください。
```


| 項目名 | 説明 |
|:-----------|:------------|
| title_mail |ユーザーまたは管理者に送信されるメールの項目タイトル |
| title_html |ユーザーまたは管理者に送信されるメールの項目タイトル |
| placeholder|フォームが未入力の時に表示するテキスト |
| class      |CSSクラス |
| input_type |フォームの種類を選択 |
| validate   |フォームの入力値チェックを選択|



<br>
# ● php mailformの動作の概要

メールフォームの画面は3画面です。動作の流れは以下の通りとなります。

#### 1.メールフォーム入力画面
<pre>
・/php_mailform/phplib/mailform/templates/<b>mail_form.html</b>（画面全体のデザイン）（Smartyテンプレート）
・/php_mailform/phplib/mailform/templates/table.html（フォーム要素テーブル）（Smartyテンプレート）
</pre>

　　<b>↓</b><br>
#### 2.メールフォーム確認画面
<pre>
・/php_mailform/phplib/mailform/templates/<b>mail_form_confirm.html</b>（画面全体のデザイン）（Smartyテンプレート）
・/php_mailform/phplib/mailform/templates/table_confirm.html（フォーム要素テーブル）（Smartyテンプレート）
</pre>

　　<b>↓</b><br>
#### 3.メールフォーム送信完了画面
<pre>
・php_mailform/php_mailform_end.html（htmlファイル）
</pre>



<br>
#  ● お問合せ
本ソフトウェアについての質問・バグ報告・要望などございましたら<br>
<a class="md" href="http://econosys-system.com/contact.php">こちらのお問い合わせフォーム</a>からなんなりとお問い合わせ下さい。

#  ● 公式サイト
[php_mailform](http://econosys-system.com/freesoft/php_mailform.html)

