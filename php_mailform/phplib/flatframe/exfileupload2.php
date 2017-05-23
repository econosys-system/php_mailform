<?php

/*
	exfileupload2.php
	copyright (c)2002-2017 econosys system
	http://econosys-system.com/

	The MIT License (MIT)

	Version 1.0	とりあえず作成
	Version 1.1	delete_tmpを追加
	Version 1.11	delete_tmpの引数が指定無しの場合、newしたときのディレクトリをセットするよう変更
	Version 2.0  データ構造を変更。ファイル名、クラス名 もそれに伴い変更
	Version 2.01 move 時に chmod するよう変更
	Version 2.02 拡張子をオリジナルファイル名から取得するよう変更
	Version 2.03 アップロード上限値を超えたときに die するように変更
	Version 2.04 拡張子取得のバグフィックス
*/

/*
	Usage

		require_once 'exfileupload2.php';
		$file = new exfileupload2( $upload_tmp_dir );
		$file->delete_tmp();
		$filelist = $file->move();
*/

class exfileupload2
{
	var $version = '2.03';
	var $notice  = 'exfileupload2.php  copyright (c)econosys system  http://econosys-system.com/  Under The MIT License (MIT)';
	var $tmp_path;
	var $filelist = array();



	// ========== コンストラクタ
	function __construct( $tmp_path = '' ){
		$this->tmp_path = $tmp_path;
	}



	// ========== ファイルの移動
	function move(){
		foreach ($_FILES as $key => $value){
			if ( $_FILES[$key]['error']==UPLOAD_ERR_INI_SIZE){
				$this->filelist["$key"]['name']='';
				$this->filelist["$key"]['uploaded_name']='';
				$this->filelist["$key"]['uploaded_basename']='';
				$this->filelist["$key"]['error']="exfileupload2 ERROR : can not upload over ".ini_get('upload_max_filesize')." Bytes";
				die($this->filelist["$key"]['error']);
			}
			else if (is_uploaded_file($_FILES[$key]['tmp_name'])){
				// $ext = $this->_get_ext($_FILES[$key]['type']);	// mimeタイプから拡張子を取得
				$ext = $this->_get_ext($_FILES[$key]['name']);	// ファイル名から拡張子を取得
				// 設定したテンポラリディレクトリに移動
				if( move_uploaded_file($_FILES[$key]['tmp_name'], $this->tmp_path.'/'.basename($_FILES[$key]['tmp_name'].$ext) ) ){
					$this->filelist["$key"]['name']=$_FILES[$key]['name'];
					$this->filelist["$key"]['type']=$_FILES[$key]['type'];
					$this->filelist["$key"]['size']=$_FILES[$key]['size'];
					$this->filelist["$key"]['ext']=$ext;
					$this->filelist["$key"]['uploaded_name']=$this->tmp_path.'/'.basename($_FILES[$key]['tmp_name'].$ext);
					$this->filelist["$key"]['uploaded_basename']=basename($_FILES[$key]['tmp_name'].$ext);
					chmod ($this->tmp_path.'/'.basename($_FILES[$key]['tmp_name'].$ext), 0666);
				}
				else{
				die('[ ERROR: can not move'.$this->tmp_path.' ]');
				}
			}
		}
		return $this->filelist;
	}



	//========== _get_ext mime-typeを調べて拡張子を返す
	function _get_ext($name){
		if      ( preg_match('/\.jpeg$/', $name) ){ return '.jpg'; }
		else if ( preg_match('/\.jpg$/', $name)  ){ return '.jpg'; }
		else if ( preg_match('/\.gif$/', $name)  ){ return '.gif'; }
		else if ( preg_match('/\.png$/', $name)  ){ return '.png'; }
		else if ( preg_match('/\.([a-zA-z0-9]+)$/', $name, $result)  ){
			$ext = strtolower($result[1]);
			return ".{$ext}";
		}
	}



	//========== delete_tmp：古い（1日以上前の）テンポラリファイルを削除する
	function delete_tmp($dirpath=''){

		if ($dirpath==''){ $dirpath=$this->tmp_path; }
		$deleted_list=array();
		$dir = dir($dirpath);
		while ( ($file=$dir->read()) !== FALSE ){
			if (preg_match('/^\./',$file)){ continue; }	// ディレクトリと .始まりのファイルは削除しない
			else {
				$filetime=filemtime("$dirpath/$file"); $nowtime=time();
				$int_f=intval($filetime);	$int_n=intval($nowtime);
				$sa=($int_n-$int_f);			// 現在の日付との差
				if ($sa > (60*60*24)){			// 24H以上前のファイルは削除
					array_push($deleted_list,$file );
					if ( ! unlink("$dirpath/$file") ){ die("ファイル[".$dirpath."/".$file."]の削除に失敗しました"); }
				}
			}
		}
		return $deleted_list;
	}



	//========== dump
	function dump($data){
		mb_convert_variables('EUC', 'auto', $data);
		print "<pre>";
		print_r($data);
		print "</pre>";
	}
}
