<?php

/*
    exvalidator.php
    copyright (c)2002-2017 econosys system
    http://econosys-system.com/

    The MIT License (MIT)

    Version 1.01 メールアドレスの検知方法を修正
    Version 1.02 CHECKBOX_NOT_BLANK の追加
    Version 1.03 CHECKBOX_NOT_BLANK の修正
    Version 1.04 CHECKBOX_NOT_BLANK の修正
    Version 1.06 convert_jquery_validate()追加
    Version 1.07 bug-fix
    Version 1.08 bug-fix
    Version 1.09 コンストラクタでyaml以外に array()も受けられるように
    Version 1.10 convert_jquery_validate() のbug-fix
    Version 1.11 eregを使用しないように
    Version 1.12 Strict Standards: Only variables should be passed by reference エラーへの対応
    Version 2.00 PHP7対応
    Version 2.01 jquery.validate エラーメッセージ日本語対応


*/

class validator
{
    public $version = '2.01';
    public $notice = 'exvalidator.php  copyright (c)econosys system  http://econosys-system.com/  Under The MIT License (MIT)';
    public $q = array();
    public $validator_config = array();
    public $result = array();



    public function __construct($array_or_yamlfile, $q)
    {
        if (is_array($array_or_yamlfile)) {
            $this->validator_config = $array_or_yamlfile;
        } else {
            require_once 'spyc/Spyc.php';
            $this->validator_config = spyc_load_file($array_or_yamlfile);
        }
        $this->q = $q;
    }



    // ヴァリデーションの実行
    public function check($group)
    {
        $validator_group = array();
        if (array_key_exists($group, $this->validator_config)) {
            $validator_group = $this->validator_config[$group];
        } else {
            die('[ error : '.__CLASS__.' ]'.'設定ファイル（YAML）内にvalidator_groupの'.$group.'をセットして下さい');
        }
        // validattionの実行
        foreach ($validator_group as $itemname => $value) {

            // my_form[] → my_form に変換
            if (preg_match('/\[\]$/', $itemname)) {
                $itemname = preg_replace('/\[\]$/', '', $itemname);
            }

            if (array_key_exists($itemname, $this->q)) {
                foreach ($value as $k => $err_message) {
                    if ($k == 'NOT_BLANK') {
                        $this->validation_not_blank($this->q[$itemname], $itemname, $err_message);
                    } elseif ($k == 'CHECKBOX_NOT_BLANK') {
                        $this->validation_checkbox_not_blank($this->q[$itemname], $itemname, $err_message);
                    } elseif ($k == 'EMAIL') {
                        $this->validation_email($this->q[$itemname], $itemname, $err_message);
                    } elseif (preg_match('/^LENGTH/', $k)) {
                        list($dummy, $min, $max) = explode(',', $k);
                        $this->validation_length($this->q[$itemname], $min, $max, $itemname, $err_message);
                    } elseif (preg_match('/^DUPLICATION/', $k)) {
                        list($dummy, $confirm_itemname) = explode(',', $k);
                        if (!isset($this->q[$confirm_itemname])) {
                            die("設定ファイル（YAML）内に【{$k}】とありますが該当フィールド（{$confirm_itemname}）が見つかりません");
                        }
                        $this->validation_duplication($this->q[$itemname], $this->q[$confirm_itemname], $itemname, $err_message);
                    } elseif (preg_match('/^REGEX/', $k)) {
                        list($dummy, $pattern) = explode(',', $k);
                        $this->validation_regex($this->q[$itemname], $pattern, $itemname, $err_message);
                    } else {
                        die('[ error : '.__CLASS__.' ]'."未知の指定 [$k] です。設定ファイル（YAML）の書式が間違っている可能性があります");
                    }
                }
            } else {
                if (isset($value['CHECKBOX_NOT_BLANK'])) {
                    $err_message = $value['CHECKBOX_NOT_BLANK'];
                    $this->validation_checkbox_not_blank('', $itemname, $err_message);    // カラ文字列を送っている
                } else {
                    die('[ error : '.__CLASS__.' ]'."フォーム項目 [$itemname] がありません");
                }
            }
        }

        return $this->result;
    }



    // ヴァリデーション(CHECKBOX_NOT_BLANK)
    public function validation_checkbox_not_blank($formvalue, $itemname, $err_message)
    {
        if (is_array($formvalue)) {
            // OK
        } else {
            if ((strcmp($formvalue, '') == 0) || (!$itemname)) {
                if (!isset($this->result[$itemname])) {
                    $this->result[$itemname] = $err_message;
                }
            }
        }
    }



    // ヴァリデーション(NOT_BLANK)
    public function validation_not_blank($formvalue, $itemname, $err_message)
    {
        if (!$formvalue) {
            if (!isset($this->result[$itemname])) {
                $this->result[$itemname] = $err_message;
            }
        }
    }



    // ヴァリデーション(EMAIL)
    public function validation_email($formvalue, $itemname, $err_message)
    {
        if (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $formvalue)) {
            if (!isset($this->result[$itemname])) {
                $this->result[$itemname] = $err_message;
            }
        }
    }



    // ヴァリデーション(LENGTH)
    public function validation_length($formvalue, $min, $max, $itemname, $err_message)
    {
        $length = strlen($formvalue);
        if (($length < $min) or ($length > $max)) {
            if (!isset($this->result[$itemname])) {
                $this->result[$itemname] = $err_message;
            }
        }
    }



    // ヴァリデーション(DUPLICATION)
    public function validation_duplication($formvalue, $confirm_formvalue, $itemname, $err_message)
    {
        if (strcmp($formvalue, $confirm_formvalue) != 0) {
            if (!isset($this->result[$itemname])) {
                $this->result[$itemname] = $err_message;
            }
        }
    }



    // ヴァリデーション(REGEX)
    public function validation_regex($formvalue, $pattern, $itemname, $err_message)
    {
        if (strcmp($formvalue, '') == 0) {
            return;
        }
        if (!preg_match('/'.$pattern.'/', $formvalue)) {
            if (!isset($this->result[$itemname])) {
                $this->result[$itemname] = $err_message;
            }
        }
    }
    // has_error
    public function has_error()
    {
        if (count($this->result)) {
            return true;
        } else {
            return false;
        }
    }



    //========== dump
    public function dump($data)
    {
        print "\n".'<pre style="text-align:left;">'."\n";
        print_r($data);
        print "</pre>\n\n";
    }



    //========== dump2
    public function dump2($data)
    {
        print "\n".'<!-- <pre style="text-align:left;">'."\n";
        print_r($data);
        print "</pre> --> \n\n";
    }



    //========== convert_jquery_validate：jquery.validate.js 用にコンバート
    public function convert_jquery_validate($form_id_name = 'MY_FORM')
    {
        $js_text = '';

$js_text .= <<< DOC_END
$.extend( $.validator.messages, {
    required: "このフィールドは必須です。",
    remote: "このフィールドを修正してください。",
    email: "有効なEメールアドレスを入力してください。",
    url: "有効なURLを入力してください。",
    date: "有効な日付を入力してください。",
    dateISO: "有効な日付（ISO）を入力してください。",
    number: "半角数字以外の文字が入力されています。",
    digits: "数字のみを入力してください。",
    creditcard: "有効なクレジットカード番号を入力してください。",
    equalTo: "同じ値をもう一度入力してください。",
    extension: "有効な拡張子を含む値を入力してください。",
    maxlength: $.validator.format( "{0} 文字以内で入力してください。" ),
    minlength: $.validator.format( "{0} 文字以上で入力してください。" ),
    rangelength: $.validator.format( "{0} 文字から {1} 文字までの値を入力してください。" ),
    range: $.validator.format( "{0} から {1} までの値を入力してください。" ),
    max: $.validator.format( "{0} 以下の値を入力してください。" ),
    min: $.validator.format( "{0} 以上の値を入力してください。" )
} );

DOC_END;

        foreach ($this->validator_config as $k => $v) {
            // 1. rules の書き出し

$js_text .= <<< DOC_END
$.validator.addMethod("myregex", function(value, element, reg_str) {
	if (value===''){ return true; }
	var re = new RegExp(reg_str);
	return re.test(value);
}, "入力値が正しくありません");
DOC_END;
            $js_text .= "\n\n";

            $js_text .= <<< DOC_END
$("#{$form_id_name}").validate({
	rules: {

DOC_END;
            foreach ($v as $kk => $vv) {
                $js_text .= "\t\t{$kk}: {\n";
                foreach ($vv as $method => $text) {
                    //$this->dump( $method );
                    if ((strcmp($method, 'NOT_BLANK') == 0) || (strcmp($method, 'CHECKBOX_NOT_BLANK') == 0)) {
                        $js_text .= ("\t\t\trequired: true");
                        $ar_key = array_keys($vv);
                        if (!(end($ar_key) === $method)) {
                            $js_text .= ',';
                        }
                        $js_text .= "\n";
                    } elseif (strcmp($method, 'EMAIL') == 0) {
                        $js_text .= ("\t\t\temail: true");
                        $ar_key = array_keys($vv);
                        if (!(end($ar_key) === $method)) {
                            $js_text .= ',';
                        }
                        $js_text .= "\n";
                    } elseif (preg_match('/^REGEX,(.+)/', $method, $r)) {
                        $pattern = $r[1];
                        $js_text .= ("\t\t\tmyregex: '{$pattern}'");
                        $ar_key = array_keys($vv);
                        if (!(end($ar_key) === $method)) {
                            $js_text .= ',';
                        }
                        $js_text .= "\n";
                    } elseif (preg_match('/^DUPLICATION,(.+)/', $method, $r)) {
                        $form_id = $r[1];
                        $js_text .= ("\t\t\tequalTo: #{$form_id}");
                        $ar_key = array_keys($vv);
                        if (!(end($ar_key) === $method)) {
                            $js_text .= ',';
                        }
                        $js_text .= "\n";
                    }
                }
                $ar_key = array_keys($v);
                if (!(end($ar_key) === $kk)) {
                    $js_text .= "\t\t} ,\n";
                } else {
                    $js_text .= "\t\t}\n";
                }
            }
            $js_text .= <<< DOC_END
	} ,

DOC_END;

            // 2. messages の書き出し
            $js_text .= <<< DOC_END
	messages: {

DOC_END;
            foreach ($v as $kk => $vv) {
                $js_text .= "\t\t{$kk}: {\n";
                foreach ($vv as $method => $text) {
                    if ((strcmp($method, 'NOT_BLANK') == 0) || (strcmp($method, 'CHECKBOX_NOT_BLANK') == 0)) {
                        $js_text .= ("\t\t\trequired: '{$text}'");
                        $ar_key = array_keys($vv);
                        if (!(end($ar_key) === $method)) {
                            $js_text .= ',';
                        }
                        $js_text .= "\n";
                    } elseif (strcmp($method, 'EMAIL') == 0) {
                        $js_text .= ("\t\t\temail: '{$text}'");
                        $ar_key = array_keys($vv);
                        if (!(end($ar_key) === $method)) {
                            $js_text .= ',';
                        }
                        $js_text .= "\n";
                    } elseif (preg_match('/^REGEX,(.+)/', $method, $r)) {
                        $js_text .= ("\t\t\tmyregex: '{$text}'");
                        $ar_key = array_keys($vv);
                        if (!(end($ar_key) === $method)) {
                            $js_text .= ',';
                        }
                        $js_text .= "\n";
                    } elseif (preg_match('/^DUPLICATION,(.+)/', $method, $r)) {
                        $js_text .= ("\t\t\tequalTo: '{$text}'");
                        $ar_key = array_keys($vv);
                        if (!(end($ar_key) === $method)) {
                            $js_text .= ',';
                        }
                        $js_text .= "\n";
                    }
                }
                $ar_key = array_keys($v);
                if (!(end($ar_key) === $kk)) {
                    $js_text .= "\t\t} ,\n";
                } else {
                    $js_text .= "\t\t}\n";
                }
            }
            $js_text .= <<< DOC_END
	} ,

DOC_END;

            // 3. エラー表示位置の書き出し
            $js_text .= <<< DOC_END
			errorPlacement: function(error,element){
				if($('#'+ element.attr('name')+'_err').length) {
				}
				else{
			    console.log('#'+ element.attr('name')+'_err' + 'が存在しません');
				}
				error.insertAfter($('#'+ element.attr('name') + '_err'));
			}
DOC_END;

            $js_text .= <<< DOC_END
});
DOC_END;
        }
        // $this->dump( $js_text );
        return $js_text;
    }
}
