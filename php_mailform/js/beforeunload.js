// UTF-8 
// beforeunload.js

jQuery(document).ready(function($) {
    $(window).on('beforeunload', function() {
        if (changeFlg) {
          return "メモ入力画面を閉じようとしています。\n入力中の情報がありますがよろしいですか？";
        }
    });
    //フォームの内容が変更されたらフラグを立てる
    $('form input').change(function() {
        changeFlg = true;
    });
    $('form textarea').change(function() {
        changeFlg = true;
    })
});

