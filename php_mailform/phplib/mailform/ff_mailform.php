<?php

// =======================================================================================
// ff_mailform.php 　The MIT License  (c)2020 econosys system  https://econosys-system.com/
// =======================================================================================
//
// Version 1.00  ：公開Ver
// Version 1.01  ：余分なコードを削除
// Version 1.02  ：環境変数を含ませる
// Version 1.03  ：submit時もvalidationするよう変更
// Version 1.05  ：subject文字化けのbug-fix
// Version 1.07  ：細かいbug-fix
// Version 1.08  ：do_confirm() の細かいbug-fix
// Version 1.12  ：[fix] 細かいbug-fix , 4対応のbootwatch
// Version 1.13  ：[fix] すべての項目を選択時ファイル選択すると挙動がおかしいバグを修正
// Version 1.14  ：[fix] checkboxの不具合修正
// Version 1.15  ：[add] 入力エリアに文字が残っている時に画面遷移しようとした時にアラート表示
// Version 1.16  ：[fix] PHP7.3対応 メールヘッダ追加して Windows10文字化け対応
// Version 1.17  ：[fix] 添付ファイルメール送信時の不具合対処
// Version 1.171 ：[fix] 添付ファイルなしの時の不具合対処
// Version 1.22  ：[fix] ssl フラグを追加
// Version 1.23  ：[fix] 複数attach file
// Version 1.30  ：[add] sendmailオプション追加
// Version 1.31  ：[add] 管理者メールに番号を追加
// Version 1.32  ：[add] {dayno}を追加
// Version 1.33  ：[add] smtp時エラーとなる現象を修正 , エラーメッセージ修正
// Version 1.34  ：[fix] TMPフォルダない時自動作成
// Version 1.37  ：[add] ユーザーへの控えを送信しないモード追加
// Version 1.38  ：[fix] ドキュメント修正


require_once dirname(__FILE__).'/../flatframe.php';
require_once __DIR__.'/../vendor/autoload.php';

class ff_mailform extends flatframe
{

    public function __construct($configfile)
    {
        $this->_ff_configfile = $configfile;
    }


    public function setup()
    {
        $this->rootdir = dirname(__FILE__);
        $this->run_modes = array(
            'default' => 'do_input' ,
            'input'   => 'do_input' ,
            'confirm' => 'do_confirm' ,
            'submit'  => 'do_submit' ,
        );
    }


    public function app_prerun()
    {
    }


    public function app_postrun()
    {
    }


    public function do_input()
    {
        $hidden = '<input type="hidden" name="cmd" value="confirm">';
        $this->template->assign(array('hidden' => $hidden));

        $mail_form_table = $this->_make_mail_form_table();
        $this->template->assign(array('mail_form_table' => $mail_form_table));

        $this->template->assign(array('config__beforeunload_js' => $this->_ff_config['beforeunload_js']));

        require_once 'FillInForm.class.php';
        $output = $this->template->fetch($this->_ff_config['template_file_input']);
        $q = $this->q;
        unset($q['cmd']);
        // fillin
        $fill = new HTML_FillInForm();
        $output = $fill->fill(array(
            'scalar' => $output,
            'fdat' => $this->_make_filin_param($q),
        ));
        print $output;
    }


    public function do_confirm()
    {
        // attach_file
        $attach_array = array();
        foreach ($this->_ff_config['form']  as $k => $v) {
            if (strcmp($v['input_type'], 'file') == 0) {
                array_push($attach_array, $k);
            }
        }
        if (count($attach_array) > 0) {
            require_once 'exfileupload2.php';
            if ( ! is_dir(dirname(__FILE__).DIRECTORY_SEPARATOR.$this->_ff_config['tmp_dir']) ){
                mkdir(dirname(__FILE__).DIRECTORY_SEPARATOR.$this->_ff_config['tmp_dir']);
            }
            $file = new exfileupload2(dirname(__FILE__).DIRECTORY_SEPARATOR.$this->_ff_config['tmp_dir']);
            $file->delete_tmp();
            $filelist = $file->move();
            foreach ($filelist as $name => $vv) {
                $this->q[$name] = array();
                $this->q[$name] = $vv;
            }
        }

        // varidation
        require_once 'Exvalidator.php';
        $validate_array = array();
        foreach ($this->_ff_config['form'] as $k => $v) {
            if ( @$v['validate'] ) {
                $validate_array['form'][$k] = $v['validate'];
            }
        }
        $validator = new Exvalidator($validate_array, $this->q);

        // 入力値チェック
        $result = $validator->check('form');
        if ($validator->has_error()) {
            $hidden = '<input type="hidden" name="cmd" value="confirm">';
            $this->template->assign(array('hidden' => $hidden));
            $this->assign(array('result' => $result));
            require_once 'FillInForm.class.php';
            $mail_form_table = $this->_make_mail_form_table();
            $this->template->assign(array('mail_form_table' => $mail_form_table));

            $output = $this->template->fetch($this->_ff_config['template_file_input']);
            $q = $this->q;
            unset($q['cmd']);
            // fillin
            $fill = new HTML_FillInForm();
            $output = $fill->fill(array(
                'scalar' => $output,
                'fdat' => $this->_make_filin_param($q),
            ));
            print $output;
            exit();
        }

        $hidden = '<input type="hidden" name="cmd" value="submit">'."\n";
        $hidden .= $this->_make_hidden_parameter();
        $this->template->assign(array('hidden' => $hidden));
        $this->template->assign(array('config__beforeunload_js' => $this->_ff_config['beforeunload_js']));
        $mail_form_table_confirm = $this->_make_mail_form_table_confirm();
        $this->template->assign(array('mail_form_table_confirm' => $mail_form_table_confirm));
        $this->template->display($this->_ff_config['template_file_confirm']);
    }


    public function do_submit()
    {
        // varidation
        require_once 'Exvalidator.php';
        $validate_array = array();
        foreach ($this->_ff_config['form'] as $k => $v) {
            if ($v['validate']) {
                $validate_array['form'][$k] = $v['validate'];
            }
        }
        $validator = new Exvalidator($validate_array, $this->q);
        // 入力値チェック
        $result = $validator->check('form');
        if ($validator->has_error()) {
            $hidden = '<input type="hidden" name="cmd" value="confirm">';
            $this->template->assign(array('hidden' => $hidden));
            $this->assign(array('result' => $result));
            require_once 'FillInForm.class.php';
            $mail_form_table = $this->_make_mail_form_table();
            $this->template->assign(array('mail_form_table' => $mail_form_table));

            $output = $this->template->fetch($this->_ff_config['template_file_input']);
            $q = $this->q;
            unset($q['cmd']);
            // fillin
            $fill = new HTML_FillInForm();
            $output = $fill->fill(array(
                'scalar' => $output,
                'fdat' => $this->_make_filin_param($q),
            ));
            print $output;
            exit();
        }

        $mail_common = $this->_make_mail_common();

        $rt_shop = $this->_mail_to_site($mail_common);

        if (!$rt_shop) {
            die("管理者へのメール送信ができませんでした。こちらからお問い合わせください。{$CONFIG['to_admin']}");
        }

        if ( @$this->_ff_config['ignore_user_mail'] == 0 ){
            $rt_customer = $this->_mail_to_customer($mail_common);
            if (!$rt_customer) {
                die("お客様へのメール送信ができませんでした。こちらからお問い合わせください。{$CONFIG['to_admin']}");
            }
        }

        if (strcmp($this->_ff_config['end_html'], '') == 0) {
            echo '<h1>メールを送信しました。</h1>';
        } else {
            header("Location: {$this->_ff_config['end_html']}");
        }
    }


    // ========== _make_mail_form_table
    public function _make_mail_form_table()
    {
        $this->template->assign(array('form_id_name' => $this->_ff_config['form_id_name']));
        $this->template->assign(array('form_class_name' => $this->_ff_config['form_class_name']));
        $this->template->assign(array('div_class_name' => $this->_ff_config['div_class_name']));
        $this->template->assign(array('ul_class_name' => $this->_ff_config['ul_class_name']));
        $this->template->assign(array('li_class_name' => $this->_ff_config['li_class_name']));
        $this->template->assign(array('row1_class_name' => $this->_ff_config['row1_class_name']));
        $this->template->assign(array('row2_class_name' => $this->_ff_config['row2_class_name']));
        $this->template->assign(array('form_loop' => $this->_ff_config['form']));
        $this->template->assign(array('yubinbango_js' => $this->_ff_config['yubinbango_js']));

        $validate_array = array();
        foreach ($this->_ff_config['form'] as $k => $v) {
            if ( @$v['validate'] ) {
                $validate_array['form'][$k] = $v['validate'];
            }
        }

        // attach_file
        $attach_array = array();
        foreach ($this->_ff_config['form']  as $k => $v) {
            if (strcmp($v['input_type'], 'file') == 0) {
                array_push($attach_array, $k);
            }
        }
        if (count($attach_array) > 0) {
            $this->template->assign(array('form_attach' => 1));
        }
        // jquery validate
        require_once 'Exvalidator.php';
        $validator = new Exvalidator($validate_array, $this->q);
        $js_text = $validator->convert_jquery_validate($this->_ff_config['form_id_name']);
        $this->template->assign(array('js_text' => $js_text));

        return $this->template->fetch('table.html');
    }


    // ========== _make_mail_form_table_confirm
    public function _make_mail_form_table_confirm()
    {
        $this->template->assign(array('q' => $this->q));
        $this->template->assign(array('form_id_name' => $this->_ff_config['form_id_name']));
        $this->template->assign(array('form_class_name' => $this->_ff_config['form_class_name']));
        $this->template->assign(array('div_class_name' => $this->_ff_config['div_class_name']));
        $this->template->assign(array('ul_class_name' => $this->_ff_config['ul_class_name']));
        $this->template->assign(array('li_class_name' => $this->_ff_config['li_class_name']));
        $this->template->assign(array('row1_class_name' => $this->_ff_config['row1_class_name']));
        $this->template->assign(array('row2_class_name' => $this->_ff_config['row2_class_name']));
        $this->template->assign(array('form_loop' => $this->_ff_config['form']));

        return $this->template->fetch('table_confirm.html');
    }


    // ========== _make_mail_common
    public function _make_mail_common()
    {
        $mail_common = '';
        foreach ($this->_ff_config['form'] as $k => $v) {
            if (is_array( @$this->q[$k] )) {
                if ( @$v['input_type'] == 'file' ){
                    $fv = $this->q[$k];
                    $mail_common .= "{$v['title_mail']} : {$fv[0]} ({$fv[2]} バイト)\n";
                }
                elseif ( @$v['input_type'] == 'checkbox' ){
                    $fv = @$this->q[$k];
                    $values = join(" , ",$fv);
                    $tm = @$v['title_mail'];
                    $mail_common .= "{$tm} :  {$values} \n";
                }
            } else {
                $fv = @$this->q[$k];
                $tm = @$v['title_mail'];
                $mail_common .= "{$tm} :  {$fv} \n";
            }
        }

        return $mail_common;
    }

    // ========== _swiftmail メール送信
    public function _swiftmail($mix = array())
    {
            // 'to'          => $to,
            // 'from'        => $from,
            // 'from_name'   => $this->_ff_config['from_name'] ,
            // 'subject'     => $subject,
            // 'cc'          => $this->_ff_config['site_cc'],
            // 'bcc'         => $this->_ff_config['site_bcc'],
            // 'replyto'     => $this->_ff_config['site_replyto'],
            // 'mailtext'    => $mailtext,
            // 'attach_file' => $attach_file,

        if ( (! $this->_ff_config['mail_method']=='sendmail') && (! $this->_ff_config['mail_method']=='smtp') ) {
            die('エラー : 設定ファイルの「メール送信方式( mail_method )」が sendmail,smtp 以外になっています。正しくサーバ情報を設定してください。');
        }

        $transport = null;

        // sendmail
        if ( $this->_ff_config['mail_method']=='sendmail' ) {
            if ( ! @$this->_ff_config['sendmail_command']=='sendmail' ) {
                die('エラー : 設定ファイルの「sendmailコマンド( mail_method )」が設定されていません。');
            }
            $transport = new Swift_SendmailTransport( $this->_ff_config['sendmail_command'] );
        }
        // smtp
        elseif ( $this->_ff_config['mail_method']=='smtp' ) {
            $smtp_option = null;
            if ( @$this->_ff_config['SMTP_option'] == 'auto' ){
                if ( @$this->_ff_config['SMTP_port'] == 465 ){
                    $ssl_flag = 'ssl';
                }
                elseif ( @$this->_ff_config['SMTP_port'] == 587 ){
                    $ssl_flag = 'tls';
                }
            }
            else {
                $smtp_option = $this->_ff_config['SMTP_option'];
            }
            if ( @$this->_ff_config['SMTP_port'] == 465 ){ $ssl_flag = 'ssl'; }
            $transport = (new Swift_SmtpTransport($this->_ff_config['SMTP_host'], $this->_ff_config['SMTP_port'], $smtp_option))
                ->setUsername($this->_ff_config['SMTP_user'])
                ->setPassword($this->_ff_config['SMTP_pass']);
        }

        $mailer = new Swift_Mailer($transport);

        // 添付ファイル（全ての添付を処理する）
        $attachment = [];
        if ( isset($mix['attach_file']) ){
            foreach ($mix['attach_file'] as $k => $v) {
                $file_full_path    = $v[4];
                $file_name         = $v[0];
                $file_content_type = $v[1];

                $attachment[$k] = \Swift_Attachment::fromPath( $file_full_path )
                ->setFilename( $file_name )
                ->setContentType( $file_content_type );
            }
        }

        $message = (new \Swift_Message('My important subject here'))
            ->setFrom([$mix['from'] => @$mix['from_name']])
            ->setTo( $mix['to'] )
            ->setSubject( $mix['subject'] )
            ->setBody( $mix['mailtext']);    // , 'text/html'

        if ( @$mix['cc'] ){
            $message->setCc($mix['cc']);
        }
        if ( @$mix['bcc'] ){
            $message->setBcc($mix['bcc']);
        }

        // replytoがある場合はセット（管理者へ送信する場合）
        if ( isset($mix['replyto']) ){
            $message->setReplyTo( $mix['replyto'] );
        }

        // 添付ファイルがある場合はセット
        // if ( count(@$mix['attach_file']) > 0 ){
        //     foreach ($mix['attach_file'] as $k => $v) {
        //         $message->attach($attachment);
        //     }
        // }
        if ( count(@$attachment) > 0 ){
            foreach ($attachment as $k => $v) {
                $message->attach($v);
            }
        }


        $result = null;

        try {
            $result = $mailer->send($message);
        } catch (\Exception $e) {
            ini_set( 'display_errors', "1" );
            error_reporting(E_ALL ^ E_NOTICE);
print <<< DOC_END
<h1>メール送信にてエラーが発生いたしました。</h1>
<h2>エラーメッセージの内容</h2>
DOC_END;
            echo '<small>' .  $e->getMessage() . "</small>\n";
            die;
        }

        return $result;

    }



    // ========== _pmail メール送信
    public function _pmail($mix = array())
    {
        $headers = array();
        foreach ($mix as $key => $value) {
            if (strcmp($key, 'subject') == 0) {
                $headers['Subject'] = mb_encode_mimeheader($mix['subject'],'ISO-2022-JP');
            } elseif (strcmp($key, 'to') == 0) {
            } elseif (strcmp($key, 'mailtext') == 0) {
            } elseif (strcmp($key, 'attach_file') == 0) {
            } else {
                if (strcmp($value, '') != 0) {
                    $headers[ucfirst($key)] = $value;
                }
            }
        }

        // PEAR:Mail
        require_once 'Mail.php';
        $objMail = false;
        $smtp_params = array();
        if (strcmp($this->_ff_config['mail_method'], 'mail') == 0) {
            $objMail = Mail::factory('mail');
        } elseif (strcmp($this->_ff_config['mail_method'], 'sendmail') == 0) {
            $objMail = Mail::factory('sendmail');
        } elseif (strcmp($this->_ff_config['mail_method'], 'smtp') == 0) {
            $smtp_params = array(
                'host' => $this->_ff_config['SMTP_host'] ,
                'port' => $this->_ff_config['SMTP_port'] ,
                'auth' => true,
                'username' => $this->_ff_config['SMTP_user'] ,
                'password' => $this->_ff_config['SMTP_pass'] ,
            );
            $objMail = Mail::factory('smtp', $smtp_params);    // mail , sendmail , smtp
        }

        // メール本文エンコード
        $mailtext = $mix['mailtext'];
        $mailtext = str_replace("\r", "\n", str_replace("\r\n", "\n", $mailtext));

        $mailtext = mb_convert_encoding($mailtext, 'ISO-2022-JP', 'UTF-8');

        // 添付ファイルがある場合はmime
        if (is_array(@$mix['attach_file'])) {


            require_once 'Mail/mime.php';

            $mime = new Mail_Mime("\n");  //改行コードをセット
            $mime->setParam('text_charset', 'ISO-2022-JP');
            $mime->setParam('text_encoding', '7bit');
            $mime->setTxtBody($mailtext);
            foreach ($mix['attach_file'] as $atk => $atv) {
                $ja_filename = $atv[0];
                mb_convert_variables('ISO-2022-JP', 'UTF-8', $ja_filename);

                $mime->addAttachment($atv[4],                // attach_file (file_path)
                    $atv[1],                // content-type
                    $ja_filename,    // ja file name
                    true,                    // false : use filename from file name ?
                    'base64',            // encoding
                    'attachment',    // disposition
                    '',                        // charset
                    '',                        // language
                    '',                        // location
                    'base64',            // n_encoding
                    'base64',            // f_encoding
                    '',                        // description
                    'ISO-2022-JP'  // h_charset
                );
            }

            $mailtext = $mime->get(array(
                'head_charset' => 'ISO-2022-JP',
                'text_charset' => 'ISO-2022-JP',
            ));
            $headers = $mime->headers($headers);
        }

        // ヘッダ追加
        $headers["MIME-Version"] = "1.0";
        $headers["Content-Type"] = "text/plain; charset=ISO-2022-JP";
        $headers["Content-Transfer-Encoding"] = "7bit";

        // 送信
        $result = $objMail->send($mix['to'], $headers, $mailtext);

        if (PEAR::isError($result)) {
            die($result->getMessage());

            return false;
        } else {
            return true;
        }
    }


    // ========== _mail_to_site （サイト管理者へメール送信）
    public function _mail_to_site($mail_common = '')
    {
        //お店メールの設定
        $subject = $this->_ff_config['site_subject'];
        $from = $this->q['email'];
        $to = $this->_ff_config['site_to'];

        // subjet に日付を追加
        $site_subject_date = null;
        if ( @$this->_ff_config['site_date_format'] ){
            date_default_timezone_set('Asia/Tokyo');
            setlocale(LC_TIME, 'ja_JP.utf8');
            $dt = new \Carbon\Carbon();
            $site_subject_date = \Carbon\Carbon::now()->format( $this->_ff_config['site_date_format'] );
            $subject = str_replace("{date}", $site_subject_date, $subject);
            // $this->dump( $subject );
        }

        // subject に count を追加
        require_once 'textdb.php';
        $count_dt = new textdb("./phplib/mailform/textdb.yml", "count", $this->_ff_config['data_dir'], 'cgi');
        $count_hash = $count_dt->select_one(array(
            'id' => 1 ,
        ));
        $now_count = (int)@$count_hash['count_no'] + 1;

        //日毎の連番
        $now_count_dayno = null;
        if ( ! isset($count_hash['id']) ){
        	$now_count_dayno = 1; 		// データがないときは1
        } else {
        	$dt_data = new \Carbon\Carbon( $count_hash['updated_at'] );
        	$dt_now  = new \Carbon\Carbon();
        	// $this->dump( $dt_data->format("Ymd") ); $this->dump( $dt_now->format("Ymd") );
        	if ( $dt_data->format("Ymd") == $dt_now->format("Ymd") ){
        		$now_count_dayno = (int)@$count_hash['count_dayno'] + 1;
        	}
    		else {
        		$now_count_dayno = 1;
    		}
        }

        // データがないときは新規作成
        if ( ! isset($count_hash['id']) ){
            $count_dt->insert(array(
				'count_no'    => 1 ,
				'count_dayno' => 1 ,
				'updated_at'  => new \Carbon\Carbon() ,
            ));
        }
        // あるときは update
        else {
            $count_dt->update(array(
				'id'          => 1 ,
				'count_no'    => $now_count ,
				'count_dayno' => $now_count_dayno ,
				'updated_at'  => new \Carbon\Carbon() ,
            ));
        }

        // subject を置換
        $subject = str_replace("{no}", sprintf("%06d",$now_count), $subject);
        $subject = str_replace("{dayno}", sprintf("%02d",$now_count_dayno), $subject);

        // $this->dump( $now_count ); $this->dump( $now_count_dayno ); $this->dump( $subject ); die;


        // 送信者情報
        require_once 'exdate.php';
        $t = new exdate();
        list($year, $month, $day, $hour, $min, $sec) = $t->now();
        $day_of_the_week = $t->day_of_the_week();
        $hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
        $info['date'] = "$year/$month/$day($day_of_the_week) $hour:$min";
        $info['host'] = $hostname;
        $info['addr'] = $_SERVER['REMOTE_ADDR'];
        $info['agent'] = $_SERVER['HTTP_USER_AGENT'];
        $info['referer'] = $_SERVER['HTTP_REFERER'];

        $this->template->assign($this->q);
        $this->template->assign(array('info' => $info));
        $this->template->assign(array('mail_common' => $mail_common));
        $mailtext = $this->template->fetch('mail_text_site.txt');

        // attach_file
        $attach_file = array();
        $attach_array = array();
        foreach ($this->_ff_config['form']  as $k => $v) {
            if (strcmp($v['input_type'], 'file') == 0) {
                array_push($attach_array, $k);
            }
        }
        foreach ($attach_array as $v) {
            if (is_file( @$this->q[$v][4] )) {
                $attach_file[$v] = $this->q[$v];
            }
        }

        $mail_hash = [
            'to'          => $to,
            'from'        => $from,
            'subject'     => $subject,
            'cc'          => $this->_ff_config['site_cc'],
            'bcc'         => $this->_ff_config['site_bcc'],
            'replyto'     => $from,
            'mailtext'    => $mailtext,
            'attach_file' => $attach_file,
        ];

        if ( $this->_ff_config['site_cc'] ){
            $mail_hash['cc'] = $this->_ff_config['site_cc'];
        }
        if ( $this->_ff_config['site_bcc'] ){
            $mail_hash['bcc'] = $this->_ff_config['site_bcc'];
        }

        $rt = $this->_swiftmail($mail_hash);

        return $rt;
    }



    // ========== _mail_to_customer
    public function _mail_to_customer($mail_common = '')
    {
        //お客様へメールの設定
        $to = $this->q['email'];
        $subject = $this->_ff_config['user_subject'];

        $this->template->assign($this->q);
        $this->template->assign(array('mail_common' => $mail_common));
        $mailtext = $this->template->fetch('mail_text_user.txt');

        //お客様へメールの送信
        $value = $this->_swiftmail(array(
            'to'        => $to,
            'from'      => $this->_ff_config['site_to'],
            'from_name' => $this->_ff_config['site_name'] ,
            'subject'   => $subject,
            'mailtext'  => $mailtext,
        ));

        return $value;
    }

    // ========== _make_hidden_parameter
    public function _make_hidden_parameter($hash = array())
    {
        if (count($hash) == 0) {
            $hash = $this->q;
        }
        $hidden = '';
        foreach ($hash as $k => $v) {
            if ($k == '_program_name') {
            } elseif ($k == '_program_uri') {
            } elseif ($k == '_template_dir') {
            } elseif ($k == 'cmd') {
            }        // cmdの場合は生成しない
            elseif ($k == 'hidden') {
            }        // hiddenの場合は生成しない
            elseif (is_array($v)) {
                foreach ($v as $kk => $vv) {
                    $vv = htmlspecialchars($vv, ENT_QUOTES, 'ISO-8859-1');
                    $hidden .= <<< DOC_END
<input type="hidden" name="{$k}[]" value="{$vv}" />
DOC_END;
                    $hidden .= "\n";
                    $hidden .= <<< DOC_END
<input type="hidden" name="{$k}__{$kk}" value="{$vv}" />
DOC_END;
                    $hidden .= "\n";
                    // $hidden.='<input type="hidden" name="'.$k.'[]" value="'.$vv.'" />'."\n";
                }
            } else {
                $v = htmlspecialchars($v, ENT_QUOTES, 'ISO-8859-1');
                $hidden .= '<input type="hidden" name="'.$k.'" value="'.$v.'" />'."\n";
            }
        }

        return $hidden;
    }
}
