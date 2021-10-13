<?php /* Smarty version 2.6.30, created on 2021-10-13 08:59:13
         compiled from table_confirm.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'table_confirm.html', 6, false),array('modifier', 'nl2br', 'table_confirm.html', 20, false),)), $this); ?>

<!-- ff_mailform.php 　The MIT License  (c)2021 econosys system  https://econosys-system.com/ -->
<!-- [table_confirm.html] -->
<form id="form_contact" method="post">
<?php echo $this->_tpl_vars['hidden']; ?>

<div class="<?php echo ((is_array($_tmp=$this->_tpl_vars['div_class_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">
<dl class="<?php echo ((is_array($_tmp=$this->_tpl_vars['ul_class_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">
<?php $_from = $this->_tpl_vars['form_loop']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }$this->_foreach['loopname'] = array('total' => count($_from), 'iteration' => 0);
if ($this->_foreach['loopname']['total'] > 0):
    foreach ($_from as $this->_tpl_vars['k'] => $this->_tpl_vars['v']):
        $this->_foreach['loopname']['iteration']++;
?>
<dt><?php echo $this->_tpl_vars['v']['title_mail']; ?>
</dt>
<dd>
<?php if (is_array ( $this->_tpl_vars['q'][$this->_tpl_vars['k']] )): ?>
	<?php if ($this->_tpl_vars['q'][$this->_tpl_vars['k']]['uploaded_basename']): ?>
		<?php $this->assign('f', $this->_tpl_vars['q'][$this->_tpl_vars['k']]); ?><?php echo ((is_array($_tmp=$this->_tpl_vars['f']['name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
 
	<?php else: ?>
		<?php $_from = $this->_tpl_vars['q'][$this->_tpl_vars['k']]; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['vvv']):
?>
			<div class="checkbox_item"><?php echo ((is_array($_tmp=$this->_tpl_vars['vvv'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
</div>
		<?php endforeach; endif; unset($_from); ?>
	<?php endif; ?>
<?php else: ?>
	<?php echo ((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['q'][$this->_tpl_vars['k']])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)))) ? $this->_run_mod_handler('nl2br', true, $_tmp) : smarty_modifier_nl2br($_tmp)); ?>

<?php endif; ?>
</dd>
<?php endforeach; endif; unset($_from); ?>
</dl><!-- [.<?php echo ((is_array($_tmp=$this->_tpl_vars['ul_class_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
] -->

<p class="text-center mt10"><button id="submit_button" class="btn btn-success notrepeat" data-loading-text="メッセージを送信する　<i class='fa fa-refresh fa-spin'></i>" >メッセージを送信する</button></p>
<p class="text-center mt15"><input type="button" value="戻る" class="btn" onclick="history.back();"></p>

</div><!-- [.<?php echo ((is_array($_tmp=$this->_tpl_vars['div_class_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
] -->
</form>

<?php echo '
<script>
  $(\'#submit_button\').on(\'click\', function () {
    $(this).button(\'loading\');
'; ?>

  	document.<?php echo ((is_array($_tmp=$this->_tpl_vars['form_id_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
.submit();
<?php echo '
  });
</script>
'; ?>


<!-- [table_confirm.html] -->