<?php

/*
    textdb.php
    copyright (c)2002-2019 econosys system
    https://econosys-system.com/

    メソッド一覧
    select()
    select_pager()
    count()
    insert()
    update()
    delete()
    delete_all()
    move_top()
    or_search()	// OR条件で検索
	psearch()	// 正規表現で検索
    help()
*/


    // Version 0.30 ：PHP7対応
    // Version 0.31 ：[add] psearch();
    // Version 0.32 ：[add] Spyc.phpがある場合は優先して読みこむように変更



class textdb
{
    public $version          = '0.32';
    public $notice           = '本プログラムは econosys system の著作物です。MIT License copyright (c)econosys system https://econosys-system.com/';
    public $table_name       = '';
    public $ext              = '';
    public $columns          = array();
    public $types            = array();
    public $options          = array();
    public $primary          = array();
    public $data_dir         = '';
    public $lfh;

    public $dump_encoding_to = null;
    public $_config          = array();
    public $dump             = 0;


    public function __construct($yml_filename, $table_name='', $data_dir='', $ext='cgi')
    {
        umask(0);

        $yml_cache_filename = $yml_filename.'.cache';

        if (strcmp($table_name, '')==0) {
            die('textdb:error: please set table_name.');
        }
        if (strcmp($data_dir, '')==0) {
            die('textdb:error: please set data_dir.');
        }
        $this->table_name = $table_name;
        $this->ext        = $ext;

        // data_dir lockfile
        $data_dir = preg_replace('/\/$/', '', $data_dir);
        $this->_check_data_dir($data_dir);
        $this->data_dir = $data_dir;

        // load YAML
        $cached_flag = 0;
        if (file_exists($yml_filename) && file_exists($yml_cache_filename)) {
            if (@filemtime($yml_filename) < @filemtime($yml_cache_filename)) {
                $cached_flag = 1;
            }
        }

        // cache がある場合
        if ($cached_flag == 1) {
            $this->_config = unserialize(file_get_contents($yml_cache_filename));
        }
        // cache ない場合
        else {

			if (is_file(dirname(__FILE__).DIRECTORY_SEPARATOR.'spyc'.DIRECTORY_SEPARATOR.'Spyc.php')) {
			    require_once 'spyc/Spyc.php';
			} else {
			    require_once 'spyc/spyc.php';
			}

            $spyc = new Spyc();
            $this->_config = $spyc->YAMLLoad($yml_filename);

            // $this->_config = Spyc::YAMLLoad($yml_filename);
            $f = fopen($yml_cache_filename, 'wb');
            fwrite($f, serialize($this->_config));
            fclose($f);
        }
        if (! isset($this->_config[$table_name]['desc'])) {
            die("textdb:error: table_name [{$table_name}] is not defined in YAML.");
        }


        // データ構造を【カラム名】【カラムタイプ】【日本語名】  【HTMLインプット形式】【コメント】に
        //             columns , types,      columns_ja,  html_input        comments
        foreach ($this->_config[$table_name]['desc'] as $k => $v) {
            array_push($this->columns, $v[0]);

            $t = '';
            if (isset($v[1])) {
                $t = $v[1];
            }
            array_push($this->types, $t);

            $op = array();
            if (isset($v['options'])) {
                $op = $v['options'];
            }
            array_push($this->options, $op);

            // $ja_c = '';
            // if ( isset($v[2]) ){ $ja_c = $v[2]; }
            // array_push($this->columns_ja,$ja_c);
            //
            // $h_i = '';
            // if ( isset($v[3]) ){ $http_negotiate_charsei = $v[3]; }
            // array_push($this->html_input, $h_i);
            //
            // $c = '';
            // if ( isset($v[4]) ){ $c = $v[4]; }
            // array_push($this->columns_ja, $c);
        }
        // primary
        $this->primary[0] = $this->columns[0];

        // $this->dump($this);
    }


    //================= _encode_csv
    public function _encode_csv($text)
    {
        $text = preg_replace('/\</', '&lt;', $text);
        $text = preg_replace('/\>/', '&gt;', $text);
        $text = preg_replace("/\r\n/", "\n", $text);
        $text = preg_replace("/\r/", "\n", $text);
        $text = preg_replace("/\n/", "<br />", $text);
        return $text;
    }


    //================= _decode_csv
    public function _decode_csv($ar=array())
    {
        $new_ar = array();
        foreach ($ar as $k => $v) {
            $v = preg_replace("/\<br \/\>/", "\n", $v);
            $v = preg_replace('/&lt;/', '<', $v);
            $v = preg_replace('/&gt;/', '>', $v);
            $new_ar[$k] = $v;
            // カラムのタイプが「csv」の場合は配列を作る
            if (strcmp($this->_check_column_data_type($k), 'csv')==0) {
                if (strcmp($v, '')!=0) {
                    $new_ar[$k.'_loop'] = preg_split('{,}', $v);
                }
            }
        }
        return $new_ar;
    }


    //================= _check_column_data_type
    public function _check_column_data_type($column_name='')
    {
        foreach ($this->_config[$this->table_name]['desc'] as $k => $v) {
            if (strcmp($v[0], $column_name)==0) {
                return $v[1];
            }
        }
    }



    //================= _check_lockfile
        public function _check_lockfile($data_dir)
        {
            if (is_file("{$data_dir}/lockfile")) {
                return true;
            }

            $dir = dir($data_dir);
            while (($file=$dir->read()) !== false) {
                if (preg_match('/^\./', $file)) {
                    continue;
                }    // skip dir , skip hidden file
        elseif (preg_match('/^lockfile[0-9]+/', $file)) {
            return true;
        }
            }
            return false;
        }



    //================= _check_data_dir
    public function _check_data_dir($data_dir)
    {
        // dir
        if (! is_dir($data_dir)) {
            die("textdb:error: data_dir [{$data_dir}] is not exists.");
        }
        // if (! $this->_check_lockfile($data_dir) ){
        // 	die("textdb:error: lock_file [{$data_dir}/lockfile] is not exists.");
        // }
        // if ( is_file("{$data_dir}/lockfile") ){
        // 	if (! chmod("{$data_dir}/lockfile", 0666) ){ die("textdb:error: cannot chmod lock_file [{$data_dir}/lockfile] ."); }
        // }
        // file, tmpfile
        if (! touch("{$data_dir}/{$this->table_name}.{$this->ext}")) {
            die("textdb:error: cannot create data_file [{$data_dir}/{$this->table_name}.{$this->ext}] .");
        }
    }



    //================= _lock
    public function _lock($options = array())
    {
        $this->lfh = array_merge(array(
                                 'dir'      => $this->data_dir,
                                 'basename' => 'lockfile',
                                 'timeout'  => 60,
                                 'trytime'  => 10), $options);
        $this->lfh['dir'] = preg_replace('{/$}', '', $this->lfh['dir']);
        $this->lfh['path'] = $this->lfh['dir'] .'/'. $this->lfh['basename'];

        for ($i = 0; $i < $this->lfh['trytime']; $i++, sleep(1)) {
            if (@rename($this->lfh['path'], $this->lfh['current'] = $this->lfh['path'] . time())) {
                return $this->lfh;
            }
        }
        $filelist = $this->_my_scandir($this->lfh['dir']);
        foreach ($filelist as $file) {
            if (preg_match('/^' . $this->lfh['basename'] . '(\d+)/', $file, $matches)) {
                if (time() - $matches[1] > $this->lfh['timeout']
                  and rename($this->lfh['dir'] .'/'. $matches[0],
                             $this->lfh['current'] = $this->lfh['path'] . time())
                ) {
                    return $this->lfh;
                }
                break;
            }
        }
        return false;
    }


    //================= _unlock
    public function _unlock()
    {
        @rename($this->lfh['current'], $this->lfh['path']);
    }


    //================= _my_scandir
    public function _my_scandir($dir)
    {
        $dh  = opendir($dir);
        $files = array();
        while (false !== ($filename = readdir($dh))) {
            $files[] = $filename;
        }
        return $files;
    }



    //================= _count_increment
    public function _count_increment()
    {
        $pid = getmypid();
        $tmp_filename = "{$this->data_dir}/{$this->table_name}.count.cgi".$pid.time();
        $filename     = "{$this->data_dir}/{$this->table_name}.count.cgi";

        // $fp =fopen($filename, 'r') or die("textdb:error cannot open count file");
        $fp    = null;
        $count = null;
        if (is_file($filename)) {
            $fp = fopen($filename, 'r');
            $count = fgets($fp, 1024);
            $count = rtrim($count);
            $count = intval($count);
        } else {
            $tmp_all = $this->select(array(), 0, 99999);
            // idで並び替え
            $primary_column_name = $this->primary[0];
            $ids = array();
            foreach ($tmp_all as $value) {
                array_push($ids, $value[$primary_column_name]);
            }
            array_multisort($ids, SORT_DESC, $tmp_all);
            $tmp_hash = @$tmp_all[0];
            $primary_column_name = $this->primary[0];
            if (isset($tmp_hash[$primary_column_name])) {
                $count = intval($tmp_hash[$primary_column_name]);
            } else {
                $count = 0;
            }
            $fp = fopen($filename, 'w');
            fwrite($fp, $count);
        }
        fclose($fp);
        $count++;

        $fptmp =fopen($tmp_filename, 'w');
        fwrite($fptmp, $count);
        fclose($fptmp);

        rename($tmp_filename, $filename);

        return intval($count);
    }



    //================= select
    public function select($hash=array(), $start=0, $limit=5)
    {
        $time_start=0;
        $time_end=0;
        $time_work=0;

        if ($this->dump==1) {
            // 時間の計測 start
            $time_start=$this->_getmicrotime();
        }

        // ORDER_BY
        $order_by = false;
        if (isset($hash['ORDER_BY'])) {
            if ($hash['ORDER_BY']) {
                $order_by = $hash['ORDER_BY'];
                unset($hash['ORDER_BY']);
            }
        }

        $select_all_flag = false;
        if (count($hash)==0) {
            $select_all_flag = true;
        }

        if ($this->dump==1) {
            print "============================== <br>SELECT  start:{$start} limit:{$limit} order_by : {$order_by}\n";
            $this->dump($hash);
        }

        // check columns
        $this->_check_columns($hash);

        // filename
        $filename     = "{$this->data_dir}/{$this->table_name}.{$this->ext}";

        // read_data(select)
        $data_i   = 0;
        $data_sum = 0;
        $debug_i  = 0;

        // ORDER BY が指定してある時はとりあず条件に合うデータを全件取得してからソート
        $limit_org = 0;
        if ($order_by) {
            $limit_org = $limit;
            $limit = 99999;
        }

        $read_data_loop = array();
        $primary_column_name = $this->primary[0];
        $fp =fopen($filename, 'r') or die("textdb:error cannot open count file ({$filename})");
        while (!feof($fp)) {
            $debug_i++;
            $line = fgets($fp, 99999);
            $h = $this->_read_data($line);
            // $this->dump($h);
            if ($h[$primary_column_name] == false) {
                continue;
            }    // skip null line.
            if (preg_match('{^#}', $line)) {
                continue;
            }    // skip Comment line.

            $flag = false;

            if ($select_all_flag) {
                $flag = true;
                $hash_total_no = 0;
                $ture_total_no = 0;
            } else {
                $hash_total_no = count($hash);
                $ture_total_no = 0;
                foreach ($hash as $k => $v) {
                    //print "キーは（{$k}）値（{$v}）で検索<br>データファイルのキー（{$k}）の値は（{$h[$k]}） <br>\n";
                    //$this->dump($h);
                    if (strcmp($h[$k], $hash[$k])==0) {
                        $flag = true;
                        $ture_total_no++;
                    }
                    // else{ $flag = false; }
                }
            }
            if ($flag && $hash_total_no==$ture_total_no) {
                if ($data_sum >= $limit) {
                    break;
                } elseif ($data_i >= $start && $data_sum < $limit) {
                    // decode
                    $h = $this->_decode_csv($h);
                    array_push($read_data_loop, $h);
                    $data_sum++;
                }
                $data_i++;
            }
        }


        // ORDER_BY
        if ($order_by && (count($read_data_loop) > 0)) {
            // ※ カラムチェック
            //$this->dump($order_by);
            $column = false;
            $order  = false;
            if (preg_match('/ /', $order_by)) {
                list($column, $order) = explode(' ', $order_by);
            } else {
                $column = $order_by;
            }
            //$this->dump($column);
            $hash = array( $column => 'dummy' );
            $this->_check_columns($hash);

            foreach ($read_data_loop as $k => $v) {
                $sort_key[$k] = $v[$column];
            }
            if (preg_match('/DESC$/', $order_by)) {
                array_multisort($sort_key, SORT_DESC, $read_data_loop);
            } else {
                array_multisort($sort_key, SORT_ASC, $read_data_loop);
            }
            //foreach ($read_data_loop as $vvv) {	print($vvv['data_id']."<br>\n"); }
            //print '<hr><hr><hr>';
            $read_data_loop = array_slice($read_data_loop, 0, $limit_org);
            //foreach ($read_data_loop as $vvv) {	print($vvv['data_id']."<br>\n"); }
        }

        if ($this->dump==1) {
            // $this->dump($read_data_loop);
            // 時間の計測 end
            $time_end=$this->_getmicrotime();
            $time_work=$time_end - $time_start;
            $this->dump("time:{$time_work}\n");
        }

        return $read_data_loop;
    }




    //================= or_search
    public function or_search($hash=array(), $start=0, $limit=5)
    {
        $time_start=0;
        $time_end=0;
        $time_work=0;

        if ($this->dump==1) {
            $time_start=$this->_getmicrotime();
        }

        // ORDER_BY
        $order_by = false;
        if (isset($hash['ORDER_BY'])) {
            if ($hash['ORDER_BY']) {
                $order_by = $hash['ORDER_BY'];
                unset($hash['ORDER_BY']);
            }
        }

        $select_all_flag = false;

        if ($this->dump==1) {
            print "============================== <br>OR_SEARCH  start:{$start} limit:{$limit} order_by : {$order_by}\n";
            $this->dump($hash);
        }

        $this->_check_columns($hash);
        $filename     = "{$this->data_dir}/{$this->table_name}.{$this->ext}";

        // read_data(select)
        $data_i   = 0;
        $data_sum = 0;
        $debug_i  = 0;

        // ORDER BY が指定してある時はとりあず条件に合うデータを全件取得してからソート
        $limit_org = 0;
        if ($order_by) {
            $limit_org = $limit;
            $limit = 999999;
        }

        $read_data_loop = array();
        $primary_column_name = $this->primary[0];
        $fp =fopen($filename, 'r') or die("textdb:error cannot open count file ({$filename})");
        while (!feof($fp)) {
            $debug_i++;
            $line = fgets($fp, 99999);
            $h = $this->_read_data($line);
            // $this->dump($h);
            if ($h[$primary_column_name] == false) {
                continue;
            }    // skip null line.
            if (preg_match('{^#}', $line)) {
                continue;
            }    // skip Comment line.

            $flag = false;

            if ($select_all_flag) {
                $flag = true;
                $hash_total_no = 0;
                // $ture_total_no = 0;
            } else {
                $hash_total_no = count($hash);
                foreach ($hash as $k => $v) {

                    if (strpos($h[$k], $hash[$k]) !== false) {
                        $flag = true;
                        break;
                    }

                }
            }
            if ($flag) {
                if ($data_sum >= $limit) {
                    break;
                } elseif ($data_i >= $start && $data_sum < $limit) {
                    // decode
                    $h = $this->_decode_csv($h);
                    array_push($read_data_loop, $h);
                    $data_sum++;
                }
                $data_i++;
            }
        }


        // ORDER_BY
        if ($order_by && (count($read_data_loop) > 0)) {
            // ※ カラムチェック
            //$this->dump($order_by);
            $column = false;
            $order  = false;
            if (preg_match('/ /', $order_by)) {
                list($column, $order) = explode(' ', $order_by);
            } else {
                $column = $order_by;
            }
            //$this->dump($column);
            $hash = array( $column => 'dummy' );
            $this->_check_columns($hash);

            foreach ($read_data_loop as $k => $v) {
                $sort_key[$k] = $v[$column];
            }
            if (preg_match('/DESC$/', $order_by)) {
                array_multisort($sort_key, SORT_DESC, $read_data_loop);
            } else {
                array_multisort($sort_key, SORT_ASC, $read_data_loop);
            }
            $read_data_loop = array_slice($read_data_loop, 0, $limit_org);
        }

        if ($this->dump==1) {
            $this->dump($read_data_loop);
            // 時間の計測 end
            $time_end=$this->_getmicrotime();
            $time_work=$time_end - $time_start;
            $this->dump("time:{$time_work}\n");
        }

        return $read_data_loop;
    }





    //================= select_pager
    public function select_pager($hash=array(), $results_per_page=20, $page_no=1)
    {
        $start = $results_per_page * ($page_no - 1);
        return ($this->select($hash, $start, $results_per_page));
    }



    //================= select_pager_all
    public function select_pager_all($hash=array(), $results_per_page=20)
    {
        $max   = $this->count($hash);

        $out = array();
        for ($i=0; $i<($max/$results_per_page); $i++) {
            //print (($max/$results_per_page)+1);
            $page = $i+1;
            $tmp =  $this->select_pager($hash, $results_per_page, $page);
            array_push($out, $tmp);
        }

        return $out;
    }



    //================= psearch
    public function psearch($hash=array(), $start=0, $limit=5)
    {
        $time_start=0;
        $time_end=0;
        $time_work=0;

        if ($this->dump==1) {
            $time_start=$this->_getmicrotime();
        }

        // ORDER_BY
        $order_by = false;
        if (isset($hash['ORDER_BY'])) {
            if ($hash['ORDER_BY']) {
                $order_by = $hash['ORDER_BY'];
                unset($hash['ORDER_BY']);
            }
        }

        $select_all_flag = false;
        if (count($hash)==0) {
            $select_all_flag = true;
        }

        if ($this->dump==1) {
            print "============================== <br>SELECT  start:{$start} limit:{$limit} order_by : {$order_by}\n";
            $this->dump($hash);
        }

        // check columns
        $this->_check_columns($hash);

        // filename
        $filename     = "{$this->data_dir}/{$this->table_name}.{$this->ext}";

        // read_data(select)
        $data_i   = 0;
        $data_sum = 0;
        $debug_i  = 0;

        // ORDER BY が指定してある時はとりあず条件に合うデータを全件取得してからソート
        $limit_org = 0;
        if ($order_by) {
            $limit_org = $limit;
            $limit = 99999;
        }

        $read_data_loop = array();
        $primary_column_name = $this->primary[0];
        $fp =fopen($filename, 'r') or die("textdb:error cannot open count file ({$filename})");
        while (!feof($fp)) {
            $debug_i++;
            $line = fgets($fp, 99999);
            $h = $this->_read_data($line);
            // $this->dump($h);
            if ($h[$primary_column_name] == false) {
                continue;
            }    // skip null line.
            if (preg_match('{^#}', $line)) {
                continue;
            }    // skip Comment line.

            $flag = false;

            if ($select_all_flag) {
                $flag = true;
                $hash_total_no = 0;
                $ture_total_no = 0;
            } else {
                $hash_total_no = count($hash);
                $ture_total_no = 0;
                foreach ($hash as $k => $v) {
                    // print "キーは（{$k}）値（{$v}）で検索<br>データファイルのキー（{$k}）の値は（{$h[$k]}） <br>\n";
                    if ( preg_match($hash[$k],$h[$k]) ){
                    // if (strcmp($h[$k], $hash[$k])==0) {
                        $flag = true;
                        $ture_total_no++;
                    }
                    // else{ $flag = false; }
                }
            }
            if ($flag && $hash_total_no==$ture_total_no) {
                if ($data_sum >= $limit) {
                    break;
                } elseif ($data_i >= $start && $data_sum < $limit) {
                    // decode
                    $h = $this->_decode_csv($h);
                    array_push($read_data_loop, $h);
                    $data_sum++;
                }
                $data_i++;
            }
        }


        // ORDER_BY
        if ($order_by && (count($read_data_loop) > 0)) {
            // ※ カラムチェック
            //$this->dump($order_by);
            $column = false;
            $order  = false;
            if (preg_match('/ /', $order_by)) {
                list($column, $order) = explode(' ', $order_by);
            } else {
                $column = $order_by;
            }
            //$this->dump($column);
            $hash = array( $column => 'dummy' );
            $this->_check_columns($hash);

            foreach ($read_data_loop as $k => $v) {
                $sort_key[$k] = $v[$column];
            }
            if (preg_match('/DESC$/', $order_by)) {
                array_multisort($sort_key, SORT_DESC, $read_data_loop);
            } else {
                array_multisort($sort_key, SORT_ASC, $read_data_loop);
            }
            $read_data_loop = array_slice($read_data_loop, 0, $limit_org);
        }

        if ($this->dump==1) {
            // $this->dump($read_data_loop);
            $time_end=$this->_getmicrotime();
            $time_work=$time_end - $time_start;
            $this->dump("time:{$time_work}\n");
        }

        return $read_data_loop;
    }




    //================= select_one
    public function select_one($hash=array())
    {
        $d = $this->dump;
        $this->dump=0;

        $time_start=0;
        $time_end=0;
        $time_work=0;
        if ($d==1) {
            // 時間の計測 start
            $time_start=$this->_getmicrotime();
        }

        $r = $this->select($hash, 0, 1);

        $this->dump=$d;

        if ($this->dump==1) {
            print "============================== <br>SELECT_ONE\n";
            $this->dump($hash);
            $this->dump($r[0]);
            // 時間の計測 end
            $time_end=$this->_getmicrotime();
            $time_work=$time_end - $time_start;
            $this->dump("time:{$time_work}\n");
        }

        if (isset($r[0])) {
            return $r[0];
        } else {
            return array();
        }
    }



    //================= count
    public function count($hash=array())
    {
        $tmp_dump = 0;
        if ($this->dump==1) {
            $tmp_dump = 1;
            $this->dump=0;
        }

        $tmp_loop = $this->select($hash, 0, 999999);
        $count = count($tmp_loop);

        if ($tmp_dump==1) {
            $this->dump = 1;
            print "==============================<br />\n";
            print "count: {$count}<br />\n";
            print "==============================<br />\n";
        }
        return $count;
    }



    //================= insert
    public function insert($hash, $flag='top')
    {
        if ($this->dump==1) {
            print "============================== <br>INSERT : \n";
            $this->dump($hash);
        }

        // _check_column
        $this->_check_columns($hash);

        // lock
        $this->_lock()or die('Lock File Error. Server Busy. Please Reload after 1 minutes .');

        // _count_increment
        $num = $this->_count_increment();
        //make_data(insert)
        $primary_column_name = $this->primary[0];
        $write_data = '';
        foreach ($this->columns as $k => $v) {
            if (strcmp($primary_column_name, $v)==0) {
                $data = $num;
            } elseif (isset($hash[$v])) {
                $data = $hash[$v];
            } else {
                $data = '';
            }
            // encode
            $data = $this->_encode_csv($data);
            $write_data .= $data.'<>';
        }
        $write_data .= $data."\n";

        // write_data
        $pid = getmypid();
        $tmp_filename = "{$this->data_dir}/{$this->table_name}.{$this->ext}".$pid.time();
        $filename     = "{$this->data_dir}/{$this->table_name}.{$this->ext}";

        $fptmp =fopen($tmp_filename, 'w');

        if ($flag=='top') {
            fwrite($fptmp, $write_data);
        }
        $fp =fopen($filename, 'r') or die("textdb:error cannot open count file ({$filename})");
        while (!feof($fp)) {
            $line = fgets($fp, 99999);    // 1行読み込み（最大99999bytes）
            fwrite($fptmp, $line);
        }
        if ($flag=='bottom') {
            fwrite($fptmp, $write_data);
        }

        fclose($fp);
        fclose($fptmp);
        rename($tmp_filename, $filename);

        if ($this->dump==1) {
            $this->dump($write_data);
        }

        // unlock
        $this->_unlock();
        return($num);
    }



    //================= find_or_create
    public function find_or_create($hash)
    {
        $r = array();
        $r = $this->select_one($hash);
        if (count($r)>0) {
            // $this->dump($r[$this->columns[0]]);
            return $r[$this->columns[0]];
        } else {
            $c = $this->insert($hash);
            return $c;
        }
    }



    //================= update
    public function update($hash)
    {
        $time_start=0;
        $time_end=0;
        $time_work=0;
        if ($this->dump==1) {
            // 時間の計測 start
            $time_start=$this->_getmicrotime();
            print "============================== <br>UPDATE : \n";
            $this->dump($hash);
        }

        // _check_column
        $this->_check_columns($hash);

        // _check_primary_key
        $this->_check_primary_key($hash);

        // lock
        $this->_lock()or die('Lock File Error. Server Busy. Please Reload after 1 minutes .');


        // filename
        $pid = getmypid();
        $tmp_filename = "{$this->data_dir}/{$this->table_name}.{$this->ext}".$pid.time();
        $filename     = "{$this->data_dir}/{$this->table_name}.{$this->ext}";

        // make_data(update)
        $read_data_hash = array();
        $primary_column_name = $this->primary[0];
        $fp =fopen($filename, 'r') or die("textdb:error cannot open count file ({$filename})");
        while (!feof($fp)) {
            $line = fgets($fp, 99999);    // 1行読み込み（最大99999bytes）
            $h = $this->_read_data($line);
            if (strcmp($h[$primary_column_name], $hash[$primary_column_name]) == 0) {
                $read_data_hash = $h;
                break;
            }
        }

        foreach ($read_data_hash as $k => $v) {
            $read_data_hash[$k] = preg_replace('/\<br \/\>/', "\n", $read_data_hash[$k]);
        }

        $num = $hash[$primary_column_name];
        $write_data = '';
        foreach ($this->columns as $k => $v) {
            if (strcmp($primary_column_name, $v)==0) {
                $data = $num;
            }

            elseif (array_key_exists($v, $hash)) {
                $data = $hash[$v];
                //$this->dump($data);
            } else {
                $read_data_hash[$v] = preg_replace("/\r\n/", "\n", $read_data_hash[$v]);
                $read_data_hash[$v] = preg_replace("/\r/", "\n", $read_data_hash[$v]);
                $data = $read_data_hash[$v];
                // print "【{$data}】";
            }
            $data = $this->_encode_csv($data);
            $write_data .= $data.'<>';
        }
        $write_data .= "\n";

        fseek($fp, 0, SEEK_SET);

        // write_data
        $primary_column_name = $this->primary[0];
        $fptmp =fopen($tmp_filename, 'w');

        while (!feof($fp)) {
            $line = fgets($fp, 99999);    // 1行読み込み（最大99999bytes）
            $h = $this->_read_data($line);
            if (strcmp($h[$primary_column_name], $hash[$primary_column_name]) == 0) {
                fwrite($fptmp, $write_data);
            } else {
                fwrite($fptmp, $line);
            }
        }
        fclose($fp);
        fclose($fptmp);
        rename($tmp_filename, $filename);

        if ($this->dump==1) {
            $this->dump(htmlspecialchars($write_data));
            // 時間の計測 end
            $time_end=$this->_getmicrotime();
            $time_work=$time_end - $time_start;
            $this->dump("time:{$time_work}\n");
        }

        // unlock
        $this->_unlock();
        return($num);
    }



    //================= move_top
    public function move_top($hash)
    {
        if ($this->dump==1) {
            print "============================== <br>MOVE_TOP : \n";
            $this->dump($hash);
        }

        // _check_column
        $this->_check_columns($hash);

        // _check_primary_key
        $this->_check_primary_key($hash);

        // lock
        $this->_lock()or die('Lock File Error. Server Busy. Please Reload after 1 minutes .');

        // filename
        $pid = getmypid();
        $tmp_filename = "{$this->data_dir}/{$this->table_name}.{$this->ext}".$pid.time();
        $filename     = "{$this->data_dir}/{$this->table_name}.{$this->ext}";

        $primary_column_name = $this->primary[0];

        $fp    = fopen($filename, 'r') or die("textdb:error cannot open count file ({$filename})");
        $fptmp = fopen($tmp_filename, 'w');

        // データ読み込み
        $h = array();
        $move_line = '';
        while (!feof($fp)) {
            $line = fgets($fp, 99999);
            $h = $this->_read_data($line);
            if (strcmp($h[$primary_column_name], $hash[$primary_column_name]) == 0) {
                $move_line = $line;
                break;
            }
        }

        // データ書き込み
        fseek($fp, 0, SEEK_SET);
        fwrite($fptmp, $move_line);
        while (!feof($fp)) {
            $line = fgets($fp, 99999);
            $h = $this->_read_data($line);
            if (strcmp($h[$primary_column_name], $hash[$primary_column_name]) == 0) {
            } else {
                fwrite($fptmp, $line);
            }
        }

        fclose($fp);
        fclose($fptmp);
        rename($tmp_filename, $filename);

        // unlock
        $this->_unlock();
    }



    //================= delete
    public function delete($hash=array())
    {
        if ($this->dump==1) {
            print "============================== <br>DELETE : \n";
            $this->dump($hash);
        }

        if (count($hash) == 0) {
            print "textdb ERROR : please set delete option.";
            return;
        }

        // _check_column
        $this->_check_columns($hash);

        // lock
        $this->_lock()or die('Lock File Error. Server Busy. Please Reload after 1 minutes .');

        // filename
        $pid = getmypid();
        $tmp_filename = "{$this->data_dir}/{$this->table_name}.{$this->ext}".$pid.time();
        $filename     = "{$this->data_dir}/{$this->table_name}.{$this->ext}";

        // delete_data
        $delete_sum = 0;
        $primary_column_name = $this->primary[0];
        $fptmp = fopen($tmp_filename, 'w');
        $fp    = fopen($filename, 'r') or die("textdb:error cannot open data file");
        while (!feof($fp)) {
            $line = fgets($fp, 99999);    // 1行読み込み（最大99999bytes）
            $h = $this->_read_data($line);

            $flag = false;
            $hash_total_no = count($hash);
            $ture_total_no = 0;

            foreach ($hash as $k => $v) {
                if (strcmp($h[$k], $hash[$k])==0) {
                    $ture_total_no++;
                }
            }
            if ($hash_total_no==$ture_total_no) {
                $flag = true;
            }

            // OFF if (strcmp($h[$primary_column_name],$hash[$primary_column_name]) == 0 ){ $delete_sum++; }
            if ($flag) {
                $delete_sum++;
            } else {
                fwrite($fptmp, $line);
            }
        }
        fclose($fp);
        fclose($fptmp);
        rename($tmp_filename, $filename);

        if ($this->dump==1) {
            print "============================== <br>\n";
            print "DELETE {$delete_sum} data(s).<br>\n";
        }

        // unlock
        $this->_unlock();

        return($delete_sum);
    }



    //================= delete_all：データの全件削除 + autoincrement値リセット
    public function delete_all()
    {

        // data
        $filename     = "{$this->data_dir}/{$this->table_name}.{$this->ext}";
        if (is_file($filename)) {
            unlink($filename);
            touch($filename);
        }

        // count
        $filename     = "{$this->data_dir}/{$this->table_name}.count.cgi";
        if (is_file($filename)) {
            unlink($filename);
        }
    }



    //================= _read_data
    public function _read_data($line='')
    {
        $line = rtrim($line);
        $hash = array();

        $line_array = array();
        $line_array = explode('<>', $line);
        $i = 0;
        foreach ($this->columns as $k => $v) {
            if (isset($line_array[$i])) {
                $hash[$v] = $line_array[$i];
            } else {
                $hash[$v] = null;
            }
            $i++;
        }
        return $hash;
    }


    //================= _check_columns（カラムが存在するかどうか調べる）
    public function _check_columns($hash)
    {
        foreach ($hash as $key => $value) {
            if ($key == 'ORDER_BY') {
                continue;
            }
            if ($key == 'SEARCH_MODE') {
                continue;
            }
            if ($key == 'RELATION') {
                continue;
            }
            if (! in_array($key, $this->columns)) {
                die("column '$key' is not find in table '{$this->table_name}'");
            }
        }
    }


    //================= _check_primary_key（IDがセットされているかどうか調べる）
    public function _check_primary_key($hash)
    {
        if (! isset($hash[$this->columns[0]])) {
            die("please set 1st column '{$this->columns[0]}' value");
        } elseif (strcmp($hash[$this->columns[0]], '')==0) {
            die("please set 1st column '{$this->columns[0]}' value");
        }
    }


    //================= dump
    public function dump($data)
    {
        if ($this->dump_encoding_to != '') {
            mb_convert_variables($this->dump_encoding_to, 'auto', $data);
        }

        print "\n".'<pre style="text-align:left;">'."\n";
        print "==============================\n";
        print_r($data);
        print "</pre>";
    }

    //================= debug_mode：ダンプモードにする
    public function dump_mode($dump_encoding_to='')
    {
        if ($dump_encoding_to != '') {
            $this->dump_encoding_to=$dump_encoding_to;
        }
        $this->dump=1;
    }

    //================= debug_mode_off：ダンプモードを抜ける
    public function dump_mode_off()
    {
        $this->dump=0;
    }


    //================= _getmicrotime：現在の時間をマイクロ秒単位で返す関数
    public function _getmicrotime()
    {
        list($msec, $sec) = explode(" ", microtime());
        return ((float)$sec + (float)$msec);
    }

    //================= help：ヘルパーメソッド(insertのテンプレート出力)
    public function help()
    {
        print<<<EOS
<pre>
========================================================================
■ textdb [version {$this->version}] HELP
========================================================================
■ データ構造
</pre>
<table border="1" style="font-size:smaller;">
<tr><th>カラム名</th><th>型（現バージョンでは未実装）</th><th>コメント</th></tr>
EOS;

        $i = 0;
        foreach ($this->columns as $v) {
            print "<tr><td>{$v}</td><td>{$this->types[$i]}</td><td>{$this->comments[$i]}</td></tr>\n";
            $i++;
        }


        print<<<EOS
</table>
<pre>
========================================================================
■ INSERT文例（コピーペーストしてPHPソースに使用してください。）

\$insert_id = \$db_{$this->table_name}->insert(array(

EOS;

        $max = 0;
        foreach ($this->columns as $v) {
            if ($max < strlen($v)) {
                $max = strlen($v);
            }
        }
        foreach ($this->columns as $v) {
            print "\t'{$v}'".str_repeat(' ', $max - strlen($v))." => \$this->q['{$v}'],\n";
        }
        print<<<EOS
));
========================================================================

EOS;

        print "</pre>";
    }
}
