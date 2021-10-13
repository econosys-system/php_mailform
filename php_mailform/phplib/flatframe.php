<?php

/*
    flatframe.php
    copyright (c)2002-2021 econosys system
    https://econosys-system.com/

    The MIT License (MIT)


    Version 0.100 PHP5.3エラー表示対応
    Version 0.110 use_path_info:1 の時 PATH_INFO から cmdを生成
    Version 0.111 use_path_info:1 の時 fub-fix
    Version 0.12  default_timezone
    Version 0.13  _make_filin_param 追加
    Version 0.14  XSS 対策
    Version 0.15  フレームワークエラーの表示を FrameworkErrorReporting で設定出来るように
    Version 0.16  メソッド sanitize_html()修正
    Version 0.17  Smarty3対応
    Version 0.18  設定ファイルの「dbAutoConnect」自体がコメントアウトされて存在しない時は DB.php を読み込まないように
    Version 0.19  YAML読み込み bug-fix, php5.6 で mbstring.internal_encoding をセットしないように
    Version 0.20  Spyc.php , spyc.php の順に読み込みに行くように変更
    Version 0.30  PHP7対応
    Version 0.31  PHP7対応のbug-fix
    Version 0.32  ドキュメント修正

*/

/*
    メソッド一覧

    print_head        : httpヘッダ出力
    print_head_nocache: httpヘッダ出力 (no-cache)
    _framework_err    : エラーメッセージ出力
    sanitize_html     : 配列（mix）の中の文字コードを再帰的にhtmlサニタイズする
    sanitize_mysql    : 配列（mix）の中の文字コードを再帰的にMySQLサニタイズする
    _make_filin_param : fillin用に フォーム名を  taggroup_id → taggroup_id[] に変える
    make_hidden       : hidden属性の自動生成
    dump              : ダンプ
    dump2             : ダンプ（HTMLコメントアウト）dumpmem：メモリ使用状況をダンプ
    dumpmem           : メモリ使用状況をダンプ（HTMLコメントアウト）
    exec_smarty_filter: Smartyフィルタの強制実行
    stop_smarty_filter: Smartyフィルタの登録解除
    _php_version_check: PHPのバージョンチェック

*/

// インクルードパスを追加
$include_path = ini_get('include_path');
$include_path = dirname(__FILE__).DIRECTORY_SEPARATOR.'flatframe'.PATH_SEPARATOR.$include_path;
ini_set('include_path', $include_path);
unset($include_path);

if (is_file(dirname(__FILE__).DIRECTORY_SEPARATOR.'flatframe'.DIRECTORY_SEPARATOR.'spyc'.DIRECTORY_SEPARATOR.'Spyc.php')) {
    require_once 'spyc/Spyc.php';
} else {
    require_once 'spyc/spyc.php';
}

class flatframe
{
    public $version = '0.30';
    public $notice = 'flatframe.php  copyright (c)econosys system  https://econosys-system.com/  Under The MIT License (MIT)';
    public $template;
    public $dsn;
    public $db;
    public $timer;
    public $q;
    public $cmd;
    public $rootdir = '.';
    public $run_modes = array();
    public $_ff_config;
    public $_ff_configfile = '';
    public $validator = null;
    public $_ff_validator_config;
    public $_ff_validator_configfile = 'validator.yml';

    //========== _framework_setup_config：コンフィグセット
    public function _framework_setup_config()
    {
        if (file_exists("$this->rootdir/$this->_ff_configfile")) {
            $spyc = new Spyc();
            $this->_ff_config = $spyc->YAMLLoad("{$this->rootdir}/{$this->_ff_configfile}");
        } else {
            die("can't open configfile '$this->rootdir/$this->_ff_configfile'");
        }

        // PHP 5.2.0 以上の場合
        if ($this->_php_version_check('5.2.0')) {
            if (isset($this->_ff_config['default_timezone'])) {
                date_default_timezone_set($this->_ff_config['default_timezone']);
            } else {
                date_default_timezone_set('Asia/Tokyo');
            }
        }

    }

    //========== _php_version_check：PHPのバージョンチェック
    public function _php_version_check($PHP_MIN_VER)
    {
        $PHP_NOW_VER = phpversion();
        if ($PHP_NOW_VER > $PHP_MIN_VER) {
            return true;
        } else {
            return false;
        }
    }

    //========== _framework_setup_mbstring：日本語関連（mbstring）セット
    public function _framework_setup_mbstring()
    {
        mb_language('ja');
        if (isset($this->_ff_config['script_encoding'])) {
            $pv = floatval(phpversion());
            if ($pv < 5.6) {
                mb_internal_encoding($this->_ff_config['script_encoding']);
                ini_set('mbstring.internal_encoding', $this->_ff_config['script_encoding']);
            }
            ini_set('mbstring.script_encoding', $this->_ff_config['script_encoding']);
        }
    }

    //========== _framework_setup_httpheader：HTTPヘッダ文字エンコードセット
    public function _framework_setup_httpheader()
    {
        if (isset($this->_ff_config['httpheader_encoding'])) {
            ini_set('default_charset', $this->_ff_config['httpheader_encoding']);
        }
    }

    //========== _framework_setup_template：テンプレートのセットアップ
    public function _framework_setup_template()
    {
        if (is_file('Smarty3/Smarty.class.php')) {
            require_once 'Smarty3/Smarty.class.php';
        } else {
            require_once 'Smarty/Smarty.class.php';
        }

        $this->template = new Smarty();
        $this->template->template_dir = $this->rootdir.'/templates/';
        $this->template->compile_dir = $this->rootdir.'/templates_c/';
        // pluginsディレクトリが存在する場合は smarty用プラグインディレクトリに追加
        if (is_dir($this->rootdir.'/plugins/')) {
            array_push($this->template->plugins_dir, $this->rootdir.'/plugins/');
        }

        // フィルター
        $this->template->autoload_filters = array();
        // prefilterが存在する場合は登録する
        if (isset($this->_ff_config['prefilter'])) {
            if (!isset($this->template->autoload_filters['pre'])) {
                $this->template->autoload_filters['pre'] = array();
            }
            array_push($this->template->autoload_filters['pre'], $this->_ff_config['prefilter']);
        }
        // postfilterが存在する場合は登録する
        if (isset($this->_ff_config['postfilter'])) {
            if (!isset($this->template->autoload_filters['post'])) {
                $this->template->autoload_filters['post'] = array();
            }
            array_push($this->template->autoload_filters['post'], $this->_ff_config['postfilter']);
        }
        // outputfilterが存在する場合は登録する
        if (isset($this->_ff_config['outputfilter'])) {
            if (!isset($this->template->autoload_filters['output'])) {
                $this->template->autoload_filters['output'] = array();
            }
            array_push($this->template->autoload_filters['output'], $this->_ff_config['outputfilter']);
        }
        // smarty_force_compileパラメーターが存在する場合はセットする
        if (isset($this->_ff_config['smarty_force_compile'])) {
            $this->template->force_compile = $this->_ff_config['smarty_force_compile'];
        }
        // smarty_compile_checkパラメーターが存在する場合はセットする
        if (isset($this->_ff_config['smarty_compile_check'])) {
            $this->template->compile_check = $this->_ff_config['smarty_compile_check'];
        }
    }

    //========== stop_smarty_filter：Smartyフィルタの登録解除
    public function stop_smarty_filter($filter = '')
    {
        if ($filter == '') {
            return false;
        } elseif ($filter == 'outputfilter') {
            unset($this->template->autoload_filters['output']);
        } elseif ($filter == 'postfilter') {
            unset($this->template->autoload_filters['post']);
        } elseif ($filter == 'prefilter') {
            unset($this->template->autoload_filters['pre']);
        }
    }

    //========== exec_smarty_filter：Smartyフィルタの強制実行
    public function exec_smarty_filter($filter = '', $html)
    {
        if ($filter == '') {
            return $html;
        } else {
            require_once $this->rootdir.'/plugins/'."{$filter}.{$this->_ff_config[$filter]}.php";
            // print $this->_ff_config[$filter];
            $html = call_user_func("smarty_{$filter}_{$this->_ff_config[$filter]}", $html, '');

            return $html;
        }
    }

    // フォームパラメータ取得
    public function _framework_setup_form()
    {
        if (isset($_SERVER['SERVER_PORT'])) {
            if ($_SERVER['SERVER_PORT'] == 443) {
                $protocol = 'https://';
            } else {
                $protocol = 'http://';
            }

            $this->q['_program_name'] = $_SERVER['PHP_SELF'];    //実行プログラム名を取得（ドキュメントルート表記）
            $this->q['_program_uri'] = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];    //実行プログラム名を取得（URI表記）
        }
        $this->q['_template_dir'] = $this->rootdir.'/templates';

        $cmd = '';
        if (getenv('REQUEST_METHOD') == 'GET') {
            foreach ($_GET as $key => $value) {
                if (is_array($value)) {    // 配列の場合は中を辿って変換する
                    $array = array();
                    foreach ($value as $k => $v) {
                        if (get_magic_quotes_gpc() == 1) {
                            $v = stripslashes($v);
                            array_push($array, $v);
                        } else {
                            array_push($array, $v);
                        }
                    }
                    $value = $array;
                } elseif (get_magic_quotes_gpc() == 1) {
                    $value = stripslashes($value);
                }
                $this->q[$key] = $value;
            }
            if (isset($_GET['cmd'])) {
                $this->cmd = $_GET['cmd'];
            }
        } elseif (getenv('REQUEST_METHOD') == 'POST') {
            foreach ($_POST as $key => $value) {
                if (is_array($value)) {    // 配列の場合は中を辿って変換する
                    $array = array();
                    foreach ($value as $k => $v) {
                        if (get_magic_quotes_gpc() == 1) {
                            $v = stripslashes($v);
                            array_push($array, $v);
                        } else {
                            array_push($array, $v);
                        }
                    }
                    $value = $array;
                } elseif (get_magic_quotes_gpc() == 1) {
                    $value = stripslashes($value);
                }
                $this->q[$key] = $value;
            }
            if (isset($_POST['cmd'])) {
                $this->cmd = $_POST['cmd'];
            }
        }
        // use_path_info
        if (@$this->_ff_config['use_path_info'] == 1) {
            $this->_get_path_info();
        }
        // エンコードを変更
        $this->convert_form($this->_ff_config['script_encoding'], $this->_ff_config['output_encoding']);
    }

    public function _get_path_info()
    {
        if (!isset($_SERVER['PATH_INFO'])) {
            if ($_SERVER['SCRIPT_NAME'] == $_SERVER['REQUEST_URI']) {
                return;
            } elseif (isset($_SERVER['QUERY_STRING'])) {
                return;
            } else {
                print 'can not get PATH_INFO';

                return;
            }
        } else {
            $pi = preg_replace('{^/}', '', $_SERVER['PATH_INFO']);
            $a = preg_split('{/}', $pi);
            $this->cmd = array_shift($a);
            $this->q['cmd'] = $this->cmd;
            $this->q['path_info_arg'] = $a;
            //
            $this->q['_program_name'] = preg_replace("{{$_SERVER['PATH_INFO']}}", '', $this->q['_program_name']);
            $this->q['_program_uri'] = preg_replace("{{$_SERVER['PATH_INFO']}}", '', $this->q['_program_uri']);
        }
    }

    // フォームパラメータ取得
    public function _OLD__framework_setup_form_OLD()
    {
        if (isset($_SERVER['SERVER_PORT'])) {
            if ($_SERVER['SERVER_PORT'] == 443) {
                $protocol = 'https://';
            } else {
                $protocol = 'http://';
            }

            $this->q['_program_name'] = $_SERVER['PHP_SELF'];    //実行プログラム名を取得（ドキュメントルート表記）
            $this->q['_program_uri'] = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];    //実行プログラム名を取得（URI表記）
        }
        $this->q['_template_dir'] = $this->rootdir.'/templates';

        $cmd = '';

        $env_request_method = getenv('REQUEST_METHOD');
        // Windows版PHPの場合
        if (strcmp($env_request_method, '') == 0) {
            $env_request_method = $_SERVER['REQUEST_METHOD'];
        }
        if ($env_request_method == 'GET') {
            foreach ($_GET as $key => $value) {
                if (is_array($value)) {    // 配列の場合は中を辿って変換する
                    $array = array();
                    foreach ($value as $k => $v) {
                        if (get_magic_quotes_gpc() == 1) {
                            $v = stripslashes($v);
                            array_push($array, $v);
                        } else {
                            array_push($array, $v);
                        }
                    }
                    $value = $array;
                } elseif (get_magic_quotes_gpc() == 1) {
                    $value = stripslashes($value);
                }
                $this->q[$key] = $value;
            }
            if (isset($_GET['cmd'])) {
                $this->cmd = $_GET['cmd'];
            }
        } elseif ($env_request_method == 'POST') {
            foreach ($_POST as $key => $value) {
                if (is_array($value)) {    // 配列の場合は中を辿って変換する
                    $array = array();
                    foreach ($value as $k => $v) {
                        if (get_magic_quotes_gpc() == 1) {
                            $v = stripslashes($v);
                            array_push($array, $v);
                        } else {
                            array_push($array, $v);
                        }
                    }
                    $value = $array;
                } elseif (get_magic_quotes_gpc() == 1) {
                    $value = stripslashes($value);
                }
                $this->q[$key] = $value;
            }
            if (isset($_POST['cmd'])) {
                $this->cmd = $_POST['cmd'];
            }
        }
        // エンコードを変更
        $this->convert_form($this->_ff_config['script_encoding'], $this->_ff_config['output_encoding']);
    }
    /*
     * フォームパラメータのエンコードを変換
     */
    public function convert_form($encoding_to, $encoding_from)
    {
        //$this->ndump($this->q);
        if (isset($this->_ff_config['script_encoding'])) {
            $encoding_to = $this->_ff_config['script_encoding'];
        }
        if (isset($this->_ff_config['output_encoding'])) {
            $encoding_from = $this->_ff_config['output_encoding'];
        }
        if (isset($this->_ff_config['httpheader_encoding'])) {
            $encoding_from = $this->_ff_config['httpheader_encoding'];
        }    // httpheader_encoding が設定してある場合は優先してコンバート元に設定する

        if (!isset($encoding_to)) {
            die("argument 'encoding_to' is not given.(please check constractor)");
        }
        foreach ($this->q as $key => $value) {
            if (is_array($value)) {    // 配列の場合は中を辿って変換する
                //$array=array();
                //foreach ($value as $k => $v){ $v=mb_convert_encoding($v, $encoding_to, $encoding_from); array_push($array, $v); }
                //$value=$array;
            } else {
                $this->q[$key] = mb_convert_encoding($value, $encoding_to, $encoding_from);
            }
        }
    }

    //========== _framework_validator_config：フォームバリデータセット
    public function _framework_validator_config()
    {
        if (file_exists("$this->rootdir/$this->_ff_validator_configfile")) {
            $this->validator = new validator("$this->rootdir/$this->_ff_validator_configfile", $this->q);
        }
    }

    //========== dump：ダンプ
    public function dump($data)
    {
        print "\n".'<pre style="text-align:left;">'."\n";
        print_r($data);
        print "</pre>\n\n";
    }

    //========== dump2：ダンプ（HTMLコメントアウト）
    public function dump2($data)
    {
        print "\n".'<!-- <pre style="text-align:left;">'."\n";
        print_r($data);
        print "</pre> --> \n\n";
    }

    //========== dumpmem：メモリ使用状況をダンプ
    public function dumpmem()
    {
        print "\n".'<pre style="text-align:left;">'."\n";
        $mem = memory_get_usage();
        $mem = number_format($mem);
        print("Memory:{$mem}");
        print "</pre>\n\n";
    }

    //========== dumpmem：メモリ使用状況をダンプ（HTMLコメントアウト）
    public function dumpmem2()
    {
        print "\n<!-- ";
        $mem = memory_get_usage();
        $mem = number_format($mem);
        print("Memory:{$mem}");
        print " -->\n";
    }

    //========== make_hidden：hidden属性の自動生成
    public function make_hidden($hash = array())
    {
        if (count($hash) == 0) {
            $hash = $this->q;
        }

        $hidden = '';
        foreach ($hash as $k => $v) {
            if (preg_match('/^_/', $k)) {
            }    // 先頭が_の場合は生成しない
            elseif ($k == 'cmd') {
            }        // cmdの場合は生成しない
            elseif ($k == 'hidden') {
            }        // hiddenの場合は生成しない
            elseif (is_array($v)) {
                foreach ($v as $kk => $vv) {
                    $vv = htmlspecialchars($vv, ENT_QUOTES, 'ISO-8859-1');
                    $hidden .= '<input type="hidden" name="'.$k.'[]" value="'.$vv.'" />'."\n";
                }
            } else {
                $v = htmlspecialchars($v, ENT_QUOTES, 'ISO-8859-1');
                $hidden .= '<input type="hidden" name="'.$k.'" value="'.$v.'" />'."\n";
            }
        }

        return $hidden;
    }

    //========== _make_filin_param
    // fillin用に フォーム名を  taggroup_id → taggroup_id[] に変える
    public function _make_filin_param($form_loop = array())
    {
        $output_looop = array();

        foreach ($form_loop as $k => $v) {
            if (is_array($v)) {
                $output_looop[$k.'[]'] = $v;
            } else {
                $output_looop[$k] = $v;
            }
        }

        return $output_looop;
    }

    //========== _framework_setup_db：データベース接続
    public function _framework_setup_db()
    {

        if (isset($this->_ff_config['dbType'])) {
        		$db_type = $this->_ff_config['dbType'];
        		if ( strcmp($db_type,'mysql')==0 ){
        			$db_type = 'mysqli';
    				}

            $this->dsn = $db_type.'://'.$this->_ff_config['dbUser'].':'.$this->_ff_config['dbPass'].'@'.$this->_ff_config['dbHost'].'/'.$this->_ff_config['dbName'];
            $this->db = DB::connect($this->dsn);

            if (PEAR::isError($this->db)) {
                if (isset($this->_ff_config['db_app_db_error'])) {
                    if ($this->_ff_config['db_app_db_error'] == 1) {
                        // app_db_errorメソッド実行
                        $exec_method = 'app_db_error';
                        if (method_exists($this, $exec_method)) {
                            call_user_func(array($this, $exec_method));
                        } else {
                            $this->_framework_err("method [$exec_method] is not defined.");
                        }

                    }
                }
                $DebugInfo = $this->db->getDebugInfo();
                print "$DebugInfo<br>¥n";
                die($this->db->getMessage());
            }
            if (isset($this->_ff_config['dbDefaultCharacterSet'])) {
                $this->db->query('SET NAMES '.$this->_ff_config['dbDefaultCharacterSet']);
                if (PEAR::isError($this->db)) {
                    exit($this->db->getMessage());
                }
            }
        }

// $this->dump( 'END:_framework_setup_db' );
    }

    //========== _framework_setup_timer：ベンチマーク/タイマーセット
    public function _framework_setup_timer()
    {
        if (isset($this->_ff_config['timerMode'])) {
            if ($this->_ff_config['timerMode'] == 1) {
                require_once 'Benchmark/Timer.php';
                $this->timer = new Benchmark_Timer();
                $this->timer->start();
                $this->timer->setMarker('Start');
            }
        }
    }

    //========== _framework_setup_error：エラー出力設定
    public function _framework_setup_error()
    {
        ini_set('display_errors', '1');

        if (isset($this->_ff_config['errorReporting'])) {
            $pv = floatval(phpversion());
            // print $pv;
            if ($pv >= 7.0) {
                error_reporting(E_ALL);
            } elseif ($pv >= 5.3) {
                // print "PHP Version : {$pv}";
                if ($this->_ff_config['errorReporting']     == 1) {
                    error_reporting(E_ALL & ~E_DEPRECATED);
                } elseif ($this->_ff_config['errorReporting'] == 0) {
                    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
                } elseif ($this->_ff_config['errorReporting'] == -1) {
                    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
                }
            } else {
                // エラー出力設定【1】全てのエラーを出力  【0】警告以外の全てのエラーを出力
                if ($this->_ff_config['errorReporting']     == 1) {
                    error_reporting(E_ALL);
                } elseif ($this->_ff_config['errorReporting'] == 0) {
                    error_reporting(E_ALL ^ E_NOTICE);
                } elseif ($this->_ff_config['errorReporting'] == -1) {
                    error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
                }
            }
        }
    }

    //========== do_timer_display：タイマー表示
    public function do_timer_display()
    {
        if (isset($this->timer)) {
            $this->timer->stop();
            print '<div align="center">'."\n";
            $this->timer->display();
            print '</div><br />'."\n";
        }
    }

    //========== sanitize_html：配列（mix）の中の文字コードを再帰的にhtmlサニタイズする
    public function sanitize_html($mix)
    {
        if (is_array($mix)) {
            foreach ($mix as $k => $v) {
                if (is_array($v)) {
                    $mix[$k] = $this->sanitize_html($mix[$k]);
                } else {
                    $mix[$k] = preg_replace('/;/', '!___escape_semicolon___!', $mix[$k]); // セミコロン
                    $mix[$k] = htmlspecialchars($mix[$k], ENT_QUOTES);
                    $mix[$k] = preg_replace('/!___escape_semicolon___!/', '&#59;', $mix[$k]); // セミコロン
                    $mix[$k] = preg_replace('{/}', '&#47;', $mix[$k]); // スラッシュ
                    $mix[$k] = preg_replace('{\\\}', '&#92;', $mix[$k]); // バックスラッシュ
                    $mix[$k] = preg_replace('{=}', '&#61;', $mix[$k]); // イコール
                }
            }

            return $mix;
        } else {
            $mix = preg_replace('/;/', '!___escape_semicolon___!', $mix); // セミコロン
            $mix = htmlspecialchars($mix, ENT_QUOTES);
            $mix = preg_replace('/!___escape_semicolon___!/', '&#59;', $mix); // セミコロン
            $mix = preg_replace('{/}', '&#47;', $mix); // スラッシュ
            $mix = preg_replace('{\\\}', '&#92;', $mix); // バックスラッシュ
            $mix = preg_replace('{=}', '&#61;', $mix); // イコール
            return $mix;
        }
    }

    //========== sanitize_mysql：配列（mix）の中の文字コードを再帰的にMySQLサニタイズする
    public function sanitize_mysql($mix)
    {
        if (is_array($mix)) {
            foreach ($mix as $k => $v) {
                if (is_array($v)) {
                    $mix[$k] = $this->sanitize_mysql($mix[$k]);
                } else {
                    //$this->dump($this->_ff_config);
                    $mix[$k] = mysqli_escape_string($mix[$k]);
                }
            }

            return $mix;
        } else {
            return mysqli_escape_string($mix);
        }
    }

    //========== convert_assign：文字コード変換 + アサイン
    public function convert_assign($conv_to, $conv_from, $mix)
    {
        if (!$conv_to == $conv_from) {
            mb_convert_variables($conv_to, $conv_from, $mix);
        }
        $this->template->assign($mix);
    }

    //========== convert_encoding_r：配列（mix）の中の文字コードを再帰的に変換する
    public function convert_encoding_r($mix = '')
    {
        if ($mix == '') {
            return $mix;
        }

        if (is_array($mix)) {
            foreach ($mix as $k => $v) {
                if (is_array($v)) {
                    $mix[$k] = $this->convert_encoding_r($mix[$k]);
                } else {
                    //$this->dump($this->_ff_config);
                    $mix[$k] = mb_convert_encoding($mix[$k], $this->_ff_config['output_encoding'], $this->_ff_config['script_encoding']);
                }
            }

            return $mix;
        } else {
            return mb_convert_encoding($this->_ff_config['output_encoding'], $this->_ff_config['script_encoding'], $mix);
        }
    }

    //========== assign：文字コード自動変換変換 + アサイン
    public function assign($mix)
    {
        if (isset($this->_ff_config['output_encoding']) && isset($this->_ff_config['script_encoding'])) {
            if (!($this->_ff_config['output_encoding'] == $this->_ff_config['script_encoding'])) {
                // mb_convert_variables($this->_ff_config['output_encoding'], $this->_ff_config['script_encoding'], $mix);
                $mix = $this->convert_encoding_r($mix);
            }
        }
        $this->template->assign($mix);
    }

    //========== prerun：
    public function do_app_prerun()
    {
        if (method_exists($this, 'app_prerun')) {
            $this->app_prerun();
        }
    }

    //========== postrun：
    public function do_app_postrun()
    {
        if (method_exists($this, 'app_postrun')) {
            $this->app_postrun();
        }
    }

    //========== do_method：メソッドの実行
    public function do_method()
    {
        $exec_method = '';
        if (array_key_exists($this->cmd, $this->run_modes)) {
            $exec_method = $this->run_modes[$this->cmd];
        } elseif ($this->cmd == '') {
            if (array_key_exists('default', $this->run_modes)) {
                $exec_method = $this->run_modes['default'];
            } else {
                $this->_framework_err('run_modes [default] is not defined.');
            }
        } else {
            $this->_framework_err('run_modes ['.$this->cmd.'] is not defined.');
        }
        // メソッド実行
        if (method_exists($this, $exec_method)) {
            call_user_func(array($this, $exec_method));
        } else {
            $this->_framework_err("method [$exec_method] is not defined.");
        }
    }

    //========== _framework_err：エラーメッセージ出力
    public function _framework_err($str)
    {
        if (isset($this->_ff_config['debugEncoding'])) {
            mb_convert_variables($this->_ff_config['debugEncoding'], 'auto', $str);
        }
        $str = $this->sanitize_html($str);
        /*
        $str = preg_replace('/;/','!___escape_semicolon___!',$str); // セミコロン
        $str = htmlspecialchars($str, ENT_QUOTES);
        $str = preg_replace('/!___escape_semicolon___!/','&#59;',$str); // セミコロン
        $str = preg_replace('{/}','&#47;',$str); // スラッシュ
        $str = preg_replace('{\\\}','&#92;',$str); // バックスラッシュ
        $str = preg_replace('{=}','&#61;',$str); // イコール
        */

        $print_err_flag = 1;
        if (isset($this->_ff_config['FrameworkErrorReporting'])) {
            if ($this->_ff_config['FrameworkErrorReporting'] == 0) {
                $print_err_flag = 0;
            }
        }

        if ($print_err_flag == 1) {
            print "=== FRAMEWORK ERROR ========================== <br />\n";
            print "$str <br />\n";
            print "============================================== <br />\n";
        } else {
            print 'Error.';
        }
        die();
    }

    //========== print_head：httpヘッダ出力
    public function print_head()
    {
        if (isset($this->_ff_config['httpheader_encoding'])) {
            header("Content-Type: text/html; charset={$this->_ff_config['httpheader_encoding']}");
        } else {
            header('Content-Type: text/html;');
        }
    }

    //========== print_head_nocache：httpヘッダ出力 (no-cache)
    public function print_head_nocache()
    {
        if (isset($this->_ff_config['httpheader_encoding'])) {
            header("Content-Type: text/html; charset={$this->_ff_config['httpheader_encoding']}");
        } else {
            header('Content-Type: text/html;');
        }
        header('Cache-Control: no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }

    //========== _create_loop
    // 引数										戻り値
    // loop    ：もとのSmarty用ループ
    // split_no：分割する数
    public function _create_loop($loop, $split_no = 2)
    {
        $new_loop = array();
        $tmp_loop = array();
        $f = 0;
        foreach ($loop as $k => $v) {
            if ($f < ($split_no - 1)) {
                array_push($tmp_loop, $v);
            } elseif ($f == ($split_no - 1)) {
                array_push($tmp_loop, $v);
                array_push($new_loop, $tmp_loop);
                $tmp_loop = array();
                $f = 0;
                continue;
            }
            ++$f;
        }
        // 途中でループが終了したときの処理
        if (count($tmp_loop) > 0) {
            array_push($new_loop, $tmp_loop);
        }

        return $new_loop;
    }

    //========== run：プログラムの起動
    public function run()
    {
        $this->_framework_setup_error();
        $this->setup();

        $this->_framework_setup_config();
        $this->_framework_setup_mbstring();
        $this->_framework_setup_httpheader();
        $this->_framework_setup_form();
//$this->_framework_validator_config();
        $this->_framework_setup_template();

				if ( ! isset($this->_ff_config['dbAutoConnect']) ){ $this->_framework_setup_db(); }
        elseif ( @$this->_ff_config['dbAutoConnect']===true ) { $this->_framework_setup_db(); }

        $this->_framework_setup_timer();

        $this->do_app_prerun();
        $this->do_method();
        $this->do_app_postrun();
        $this->do_timer_display();
    }
}
