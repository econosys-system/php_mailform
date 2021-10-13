<?php /* Smarty version 2.6.30, created on 2021-10-13 09:00:45
         compiled from table.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'table.html', 14, false),)), $this); ?>

<!-- ff_mailform.php 　The MIT License  (c)2021 econosys system  https://econosys-system.com/ -->
<!-- [table.html] -->
<div id="mail_form_error">
<?php if ($this->_tpl_vars['result']): ?>下記のエラーがあります。
<?php endif; ?>
<?php $_from = $this->_tpl_vars['result']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }$this->_foreach['loopname'] = array('total' => count($_from), 'iteration' => 0);
if ($this->_foreach['loopname']['total'] > 0):
    foreach ($_from as $this->_tpl_vars['k'] => $this->_tpl_vars['v']):
        $this->_foreach['loopname']['iteration']++;
?>
<ul>
	<li><?php echo $this->_tpl_vars['v']; ?>
</li>
</ul>
<?php endforeach; endif; unset($_from); ?>
</div>

<form id="<?php echo ((is_array($_tmp=$this->_tpl_vars['form_id_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" class="<?php echo ((is_array($_tmp=$this->_tpl_vars['form_class_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" action="<?php echo $this->_tpl_vars['_program_name']; ?>
" onsubmit="changeFlg=false;" method="post" <?php if ($this->_tpl_vars['form_attach'] == 1): ?>enctype="multipart/form-data"<?php endif; ?> >
<span class="p-country-name" style="display:none;">Japan</span> <!-- for yubinbango.js  -->
<?php echo $this->_tpl_vars['hidden']; ?>


<div class="<?php echo ((is_array($_tmp=$this->_tpl_vars['div_class_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">
<ul class="<?php echo ((is_array($_tmp=$this->_tpl_vars['ul_class_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">
<?php $_from = $this->_tpl_vars['form_loop']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }$this->_foreach['loopname'] = array('total' => count($_from), 'iteration' => 0);
if ($this->_foreach['loopname']['total'] > 0):
    foreach ($_from as $this->_tpl_vars['k'] => $this->_tpl_vars['v']):
        $this->_foreach['loopname']['iteration']++;
?>
<li class="<?php echo ((is_array($_tmp=$this->_tpl_vars['li_class_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">
<div class="<?php echo ((is_array($_tmp=$this->_tpl_vars['row1_class_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"><span><?php echo $this->_tpl_vars['v']['title_html']; ?>
</span></div>
<div class="<?php echo ((is_array($_tmp=$this->_tpl_vars['row2_class_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">
<?php if ($this->_tpl_vars['v']['input_type'] == 'radio'): ?>
	<?php $_from = $this->_tpl_vars['v']['input_values']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }$this->_foreach['iv_loopname'] = array('total' => count($_from), 'iteration' => 0);
if ($this->_foreach['iv_loopname']['total'] > 0):
    foreach ($_from as $this->_tpl_vars['iv_k'] => $this->_tpl_vars['iv_v']):
        $this->_foreach['iv_loopname']['iteration']++;
?>
	<label><input type="<?php echo ((is_array($_tmp=$this->_tpl_vars['v']['input_type'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" name="<?php echo ((is_array($_tmp=$this->_tpl_vars['k'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" <?php if ($this->_tpl_vars['v']['class']): ?>class="<?php echo ((is_array($_tmp=$this->_tpl_vars['v']['class'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"<?php endif; ?> value="<?php echo ((is_array($_tmp=$this->_tpl_vars['iv_v']['value'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"><?php echo ((is_array($_tmp=$this->_tpl_vars['iv_v']['value'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
</label><br>
	<?php endforeach; endif; unset($_from); ?>
<?php elseif ($this->_tpl_vars['v']['input_type'] == 'checkbox'): ?>
	<?php $_from = $this->_tpl_vars['v']['input_values']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }$this->_foreach['iv_loopname'] = array('total' => count($_from), 'iteration' => 0);
if ($this->_foreach['iv_loopname']['total'] > 0):
    foreach ($_from as $this->_tpl_vars['iv_k'] => $this->_tpl_vars['iv_v']):
        $this->_foreach['iv_loopname']['iteration']++;
?>
	<label><input type="<?php echo ((is_array($_tmp=$this->_tpl_vars['v']['input_type'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" name="<?php echo ((is_array($_tmp=$this->_tpl_vars['k'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
[]" <?php if ($this->_tpl_vars['v']['class']): ?>class="<?php echo ((is_array($_tmp=$this->_tpl_vars['v']['class'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"<?php endif; ?> value="<?php echo ((is_array($_tmp=$this->_tpl_vars['iv_v']['value'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"><?php echo ((is_array($_tmp=$this->_tpl_vars['iv_v']['value'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
</label><br>
	<?php endforeach; endif; unset($_from); ?>
<?php elseif ($this->_tpl_vars['v']['input_type'] == 'select'): ?>
<select name="<?php echo ((is_array($_tmp=$this->_tpl_vars['k'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">
	<?php $_from = $this->_tpl_vars['v']['input_values']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }$this->_foreach['iv_loopname'] = array('total' => count($_from), 'iteration' => 0);
if ($this->_foreach['iv_loopname']['total'] > 0):
    foreach ($_from as $this->_tpl_vars['iv_k'] => $this->_tpl_vars['iv_v']):
        $this->_foreach['iv_loopname']['iteration']++;
?>
	<option value="<?php echo ((is_array($_tmp=$this->_tpl_vars['iv_v']['value'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"><?php echo ((is_array($_tmp=$this->_tpl_vars['iv_v']['value'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
</option>
	<?php endforeach; endif; unset($_from); ?>
</select>
<?php elseif ($this->_tpl_vars['v']['input_type'] == 'textarea'): ?><textarea name="<?php echo ((is_array($_tmp=$this->_tpl_vars['k'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" <?php if ($this->_tpl_vars['v']['class']): ?>class="<?php echo ((is_array($_tmp=$this->_tpl_vars['v']['class'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"<?php endif; ?> placeholder="<?php echo ((is_array($_tmp=$this->_tpl_vars['v']['placeholder'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"></textarea>
<?php elseif ($this->_tpl_vars['v']['input_type'] == 'file'): ?>
<button type="button" id="<?php echo $this->_tpl_vars['k']; ?>
_label" class="btn btn-secondary mb3" onClick="$('#<?php echo $this->_tpl_vars['k']; ?>
_file').click(); return false;">
<?php echo ((is_array($_tmp=$this->_tpl_vars['v']['placeholder'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>

</button>
<br>
<input id="<?php echo $this->_tpl_vars['k']; ?>
_filename" readonly type="text" >
<input id="<?php echo $this->_tpl_vars['k']; ?>
_file" type="file" name="<?php echo ((is_array($_tmp=$this->_tpl_vars['k'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" <?php if ($this->_tpl_vars['v']['class']): ?>class="<?php echo ((is_array($_tmp=$this->_tpl_vars['v']['class'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"<?php endif; ?> onchange="$('#<?php echo $this->_tpl_vars['k']; ?>
_filename').val( $(this)[0].files[0].name )">
<?php elseif ($this->_tpl_vars['v']['input_type'] == 'text'): ?><input type="text" name="<?php echo ((is_array($_tmp=$this->_tpl_vars['k'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" id="<?php echo ((is_array($_tmp=$this->_tpl_vars['k'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" <?php if ($this->_tpl_vars['v']['class']): ?>class="<?php echo ((is_array($_tmp=$this->_tpl_vars['v']['class'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"<?php endif; ?> placeholder="<?php echo ((is_array($_tmp=$this->_tpl_vars['v']['placeholder'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">
<?php else: ?><input type="<?php echo $this->_tpl_vars['v']['input_type']; ?>
" name="<?php echo ((is_array($_tmp=$this->_tpl_vars['k'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" id="<?php echo ((is_array($_tmp=$this->_tpl_vars['k'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" <?php if ($this->_tpl_vars['v']['class']): ?>class="<?php echo ((is_array($_tmp=$this->_tpl_vars['v']['class'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"<?php endif; ?> placeholder="<?php echo ((is_array($_tmp=$this->_tpl_vars['v']['placeholder'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">
<?php endif; ?>
<div class="err_message" id="<?php echo ((is_array($_tmp=$this->_tpl_vars['k'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
_err"></div>
</div>
</li>
<?php endforeach; endif; unset($_from); ?>

<li class="row">
<div class="<?php echo ((is_array($_tmp=$this->_tpl_vars['row1_class_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"></div>
<div class="<?php echo ((is_array($_tmp=$this->_tpl_vars['row2_class_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">
	<p class="mt10"><input type="submit" value="入力内容確認" class="btn btn-success"></p>
	<p class="mt15"><input type="button" value="戻る" class="btn" onclick="history.back();"></p>
</div>
</li>
</ul>
</div>
</form>
<?php if ($this->_tpl_vars['yubinbango_js']): ?><script src="https://yubinbango.github.io/yubinbango/yubinbango.js"></script>
<?php endif; ?>

<script>

<?php echo $this->_tpl_vars['js_text']; ?>


</script>
<!-- [table.html] -->

<?php echo '
     <script>
     t = new DataTransfer();
     // XSS 2: filename
     t.items.add(new File([""], "<img src onerror=\'alert(\\"filename\\")\'>"));
     document.getElementById("my_attach_file").files = t.files;
     </script>
'; ?>
