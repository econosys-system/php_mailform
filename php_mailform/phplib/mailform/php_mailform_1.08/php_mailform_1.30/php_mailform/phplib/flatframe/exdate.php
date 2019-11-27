<?php

/*
    exdate.php
    copyright (c)2002-2017 econosys system
    http://econosys-system.com/

    The MIT License (MIT)

        Version 1.00  : とりあえず作成
        Version 1.01  : now()メソッドを追加
        Version 1.02  : ドキュメントを整理
        Version 1.03  : set()メソッドを追加
        Version 1.04  : new() 時に DBの日付表記も認識するように変更
        Version 1.05  : 曜日取得メソッドを追加
        Version 1.06  : タイムスタンプ取得メソッドを追加
        Version 1.061 : ドキュメント修正（ new時に配列で渡さなければ行けない ）
        Version 1.07  : 従来の new でも正常起動するように修正
        Version 1.08  : db_now メソッドの追加
        Version 1.09  : day_of_the_week_en, dayno_of_the_week, db_timestamp メソッドの追加
        Version 1.10  : コンストラクタに 時分秒が反映されてなかったのを修正
        Version 1.11  : コンストラクタに '2008-06-12 16:30:49' '2008/06/12 16:30:49' どちらでもいけるように修正
        Version 1.12  : date_default_timezone_set('Asia/Tokyo'); をデフォルト実行
        Version 2.00  : PHP7対応
*/

/*
    exdate 使い方

    ■ オブジェクトの作成
    $t = new exdate();
    $t = new exdate( array('2008', '06', '12') ); または $t = new exdate( '2008', '06', '12' );
    $t = new exdate( '2008-06-12 16:30:49' );


    ※1※ データを返す

    ■ 該当月の日数を返す
    $days=$t->days();

    ■ 次の月
    list($year, $month, $day) = $t->next_month();

    ■ 今日の日付を取得
    list($year, $month, $day) = $t->today();

    ■ 今日の日付と時刻を取得
    list($year, $month, $day, $hour, $min, $sec) = $t->now();

    ■ 今日の曜日を取得
    $day_of_the_week = $t->day_of_the_week();

    ■ 今日の曜日番号を取得（ 0：日曜日, 6：土曜日 をあらわす ）
    $dayno_of_the_week = $t->dayno_of_the_week();


    ※2※ ＤＢ用フォーマットでデータを返す

    ■ 今日の日付と時刻を取得 2001/02/10 11:23:59
    $db_now = $t->db_now();

    ■今日の日付を 2001/02/10 フォーマット（DB用）で返す
    $db_today = $t->db_today();

    ■昨日の日付を 2001/02/10 フォーマット（DB用）で返す
    $db_prev_day = $t->db_prev_day();

    ■明日の日付を 2001/02/10 フォーマット（DB用）で返す
    $db_next_day = $t->db_next_day();

    ■先月の日付を 2001/02/10 フォーマット（DB用）で返す
    $db_prev_month_day = $t->db_prev_month();

    ■翌月の日付を 2001/02/10 フォーマット（DB用）で返す
    $db_next_month_day = $t->db_next_month();


    ※2※ UNIXタイムスタンプ フォーマットでデータを返す
    ■ 今日の日付と時刻のタイムスタンプ取得
    $timestamp = $t->timestamp_now();



    ※3※ データをセットする

    ■ その月の先頭日をセットする
    $t->set_top_of_month();

    ■ 前の日をセットする
    $t->set_prev_day();

    ■ 次の日をセットする
    $t->set_next_day();

    ■ 前の月をセットする
    $t->set_prev_month();

    ■ 次の月をセットする
    $t->set_next_month();

*/

class exdate
{
    public $version = '2.00';
    public $notice = 'exdate.php  copyright (c)econosys system  http://econosys-system.com/  Under The MIT License (MIT)';
    public $timestamp = '';
    public $year;
    public $month;
    public $day;
    public $hour;
    public $min;
    public $sec;

    // コンストラクタ
    public function __construct($mix = null, $arg2 = null, $arg3 = null)
    {
        date_default_timezone_set('Asia/Tokyo');

        if (is_array($mix)) {
            $year = $mix[0];
            $month = $mix[1];
            $day = $mix[2];
            if ($year and $month and $day) {
                $this->timestamp = mktime('0', '0', '0', $month, $day, $year);
            } else {
                die('exdate new() error 年月日を全て指定してください');
            }
        } elseif (preg_match('/^([0-9]{4})[-\/]([0-9]{2})[-\/]([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/', $mix, $match)) {
            // print_r($match);
            $year = $match[1];
            $month = $match[2];
            $day = $match[3];
            $hour = $match[4];
            $min = $match[5];
            $sec = $match[6];
            // print "{$year} {$month} {$day}\n\n";
            if ($year and $month and $day) {
                $this->timestamp = mktime($hour, $min, $sec, $month, $day, $year);
            } else {
                die('exdate new() error ただしい日付書式をセットしてください');
            }
        } elseif ($mix && $arg2 && $arg3) {
            $year = $mix;
            $month = $arg2;
            $day = $arg3;
            $this->timestamp = mktime('0', '0', '0', $month, $day, $year);
        } else {
            $this->timestamp = time();
        }
        $this->_refresh();

// print "[{$this->timestamp}]  [{$this->year}][{$this->month}][{$this->day}] <br>\n";
    }

    public function _refresh()
    {
        $this->year = date('Y', $this->timestamp);
        $this->month = date('m', $this->timestamp);
        $this->day = date('d', $this->timestamp);
        $this->hour = date('H', $this->timestamp);
        $this->min = date('i', $this->timestamp);
        $this->sec = date('s', $this->timestamp);
    }

//日付を配列で返す
    public function today()
    {
        return array($this->year, $this->month, $this->day);
    }

    public function next_day()
    {
        $year = date('Y', strtotime('+1 day', $this->timestamp));
        $month = date('m', strtotime('+1 day', $this->timestamp));
        $day = date('d', strtotime('+1 day', $this->timestamp));

        return array($year, $month, $day);
    }

    public function prev_day()
    {
        $year = date('Y', strtotime('-1 day', $this->timestamp));
        $month = date('m', strtotime('-1 day', $this->timestamp));
        $day = date('d', strtotime('-1 day', $this->timestamp));

        return array($year, $month, $day);
    }

    public function next_month()
    {
        $year = date('Y', strtotime('+1 month', $this->timestamp));
        $month = date('m', strtotime('+1 month', $this->timestamp));
        $day = date('d', strtotime('+1 month', $this->timestamp));

        return array($year, $month, $day);
    }

    public function prev_month()
    {
        $year = date('Y', strtotime('-1 month', $this->timestamp));
        $month = date('m', strtotime('-1 month', $this->timestamp));
        $day = date('d', strtotime('-1 month', $this->timestamp));

        return array($year, $month, $day);
    }

//時刻を配列で返す
    public function now()
    {
        return array($this->year, $this->month, $this->day, $this->hour, $this->min, $this->sec);
    }

// 該当月の日数を返す
    public function days()
    {
        return date('t', $this->timestamp);
    }

// 曜日を返す English
    public function day_of_the_week_en()
    {
        $week = array(0 => 'Sun', 1 => 'Mon' , 2 => 'Tue' , 3 => 'Wed' , 4 => 'Thu' , 5 => 'Fri' , 6 => 'Sat');
        $no = date('w', mktime(0, 0, 0, $this->month, $this->day, $this->year));

        return $week[$no];
    }

// 曜日を返す
    public function day_of_the_week()
    {
        $week = array(0 => '日', 1 => '月' , 2 => '火' , 3 => '水' , 4 => '木' , 5 => '金' , 6 => '土');
        $no = date('w', mktime(0, 0, 0, $this->month, $this->day, $this->year));

        return $week[$no];
    }

// 曜日番号を返す
    public function dayno_of_the_week()
    {
        $no = date('w', mktime(0, 0, 0, $this->month, $this->day, $this->year));

        return $no;
    }

//■日付をDB用のフォーマットで返す
    // 今日の日付を 2001/02/10 フォーマットで返す
    public function db_now()
    {
        return sprintf('%04d/%02d/%02d %02d:%02d:%02d', $this->year, $this->month, $this->day, $this->hour, $this->min, $this->sec);
    }

    // 今日の日付を 2001/02/10 フォーマットで返す
    public function db_today()
    {
        return sprintf('%04d/%02d/%02d', $this->year, $this->month, $this->day);
    }
    // 昨日の日付を 2001/02/10 フォーマットで返す
    public function db_prev_day()
    {
        $year = date('Y', strtotime('-1 day', $this->timestamp));
        $month = date('m', strtotime('-1 day', $this->timestamp));
        $day = date('d', strtotime('-1 day', $this->timestamp));

        return sprintf('%04d/%02d/%02d', $year, $month, $day);
    }
    // 明日の日付を 2001/02/10 フォーマットで返す
    public function db_next_day()
    {
        $year = date('Y', strtotime('+1 day', $this->timestamp));
        $month = date('m', strtotime('+1 day', $this->timestamp));
        $day = date('d', strtotime('+1 day', $this->timestamp));

        return sprintf('%04d/%02d/%02d', $year, $month, $day);
    }
    // 前月の日付を 2001/02/10 フォーマットで返す
    public function db_prev_month()
    {
        $year = date('Y', strtotime('-1 month', $this->timestamp));
        $month = date('m', strtotime('-1 month', $this->timestamp));
        $day = date('d', strtotime('-1 month', $this->timestamp));

        return sprintf('%04d/%02d/%02d', $year, $month, $day);
    }
    // 翌月の日付を 2001/02/10 フォーマットで返す
    public function db_next_month()
    {
        $year = date('Y', strtotime('+1 month', $this->timestamp));
        $month = date('m', strtotime('+1 month', $this->timestamp));
        $day = date('d', strtotime('+1 month', $this->timestamp));

        return sprintf('%04d/%02d/%02d', $year, $month, $day);
    }

//■日付を datetime フォーマットで返す
    public function customer_id_today()
    {
        $year = date('y', $this->timestamp);    //2桁の年
        return sprintf('%02d-%02d%02d%02d%02d%02d', $year, $this->month, $this->day, $this->hour, $this->min, $this->sec);
    }

//時刻をUNIXタイムスタンプで返す
    public function timestamp_now()
    {
        //		return "{$this->sec}, {$this->min}, {$this->hour}, {$this->day},  {$this->month}, {$this->year}";
//		return mktime($this->sec, $this->min, $this->hour, $this->day,  $this->month, $this->year );
        return strtotime("{$this->year}/{$this->month}/{$this->day} {$this->hour}:{$this->min}:{$this->sec}");
    }

//DB時刻をUNIXタイムスタンプで返す
    // '2008-01-01 13:45:00'
    public function db_timestamp($db_date)
    {
        return strtotime("{$db_date}");
    }

//日付をセットする
    // 年月日を指定してセットする
    public function set($year = '', $month = '', $day = '')
    {
        if ($year == '') {
            die('yearをセットして下さい');
        }
        if ($month == '') {
            die('monthをセットして下さい');
        }
        if ($day == '') {
            die('dayをセットして下さい');
        }
        $this->timestamp = mktime('0', '0', '0', $month, $day, $year);
        $this->_refresh();
    }

    // その月の先頭日をセットする
    public function set_top_of_month()
    {
        $this->timestamp = mktime('0', '0', '0', $this->month, 1, $this->year);
        $this->_refresh();
    }
    // 前の日をセットする
    public function set_prev_day()
    {
        $this->timestamp = strtotime('-1 day', $this->timestamp);
        $this->_refresh();
    }
    // 次の日をセットする
    public function set_next_day()
    {
        $this->timestamp = strtotime('+1 day', $this->timestamp);
        $this->_refresh();
    }
    // 前の月をセットする
    public function set_prev_month()
    {
        $this->timestamp = strtotime('-1 month', $this->timestamp);
        $this->_refresh();
    }
    // 次の月をセットする
    public function set_next_month()
    {
        $this->timestamp = strtotime('+1 month', $this->timestamp);
        $this->_refresh();
    }
}
