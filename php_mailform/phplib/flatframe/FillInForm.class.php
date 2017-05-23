<?php

// modified version 1.1 <input type="number"> <input type="tel"> など HTML5対応

class HTML_FillInForm {
    var $ignore_fields = array();
    var $disable_fields = array();
    var $objects = array();
    var $object_param_cache = array();
    var $fdat = array();
    var $attrs = array();
    var $select = array();
    var $current_form = null;
    var $target = null;
    var $fill_password = true;
    var $escape = 'htmlspecialchars';
    function __construct() {
    }
    function fill($option = array()) {
        foreach (array('ignore_fields', 'disable_fields') as $optkey) {
            if (array_key_exists($optkey, $option)) {
                $fields = is_array($option[$optkey]) ? $option[$optkey] : array($option[$optkey]);
                foreach ($fields as $field) {
                    $tmp = & $this->$optkey;
                    $tmp[$field] = 1;
                }
            }
        }
        if (array_key_exists('fdat', $option)) {
            foreach ($option['fdat'] as $key => $val) {
                if (array_key_exists($key, $this->ignore_fields)) continue;
                $this->fdat[$key] = $val;
            }
        }
        if (array_key_exists('fobject', $option)) {
            $objects = is_array($option['fobject']) ? $option['fobject'] : array(&$option['fobject']);
            foreach ($objects as $i => $dummy) {
                if (!method_exists($objects[$i], 'param')) {
                    trigger_error("HTML_FillInForm->fill called with fobject option, containing object of type " . get_class($objects[$i]) . " which lacks a param() method!", E_USER_ERROR);
                }
                $this->objects[] = & $objects[$i];
            }
        }
        if (array_key_exists('target', $option)) {
            $this->target = $option['target'];
        }
        if (isset($option['fill_password'])) {
            $this->fill_password = $option['fill_password'];
        }
        if (array_key_exists('escape', $option)) {
            if (is_null($option['escape'])) {
                $this->escape = array(&$this, '_no_escape');
            } else {
                $this->escape = $option['escape'];
            }
        }
        $html = '';
        if (array_key_exists('file', $option)) {
            $html = function_exists('file_get_contents') ? file_get_contents($option['file']) : implode('', file($option['file']));
        } elseif (array_key_exists('scalar', $option)) {
            $html = & $option['scalar'];
        } elseif (array_key_exists('scalarref', $option)) {
            $html = & $option['scalarref'];
        } elseif (array_key_exists('array', $option)) {
            $html = implode('', $option['array']);
        } elseif (array_key_exists('arrayref', $option)) {
            $html = implode('', $option['arrayref']);
        }
        $html = preg_replace_callback('/<\s*(FORM)\s+([^>]*)>.*?<\/\s*FORM\s*>/is', array(&$this, '_parse'), $html);
        $this->_init();
        return $html;
    }
    function _init() {
        $this->ignore_fields = array();
        $this->disable_fields = array();
        $this->objects = array();
        $this->object_param_cache = array();
        $this->fdat = array();
        $this->select = array();
        $this->current_form = null;
        $this->target = null;
        $this->fill_password = true;
        $this->escape = 'htmlspecialchars';
    }
    function fill_file($file, $option = array()) {
        $option['file'] = $file;
        return $this->fill($option);
    }
    function fill_scalar($scalar, $option = array()) {
        $option['scalar'] = $scalar;
        return $this->fill($option);
    }
    function fill_array($array, $option = array()) {
        $option['array'] = $array;
        return $this->fill($option);
    }
    function _parse($matches) {
        list($origtext, $type, $attr) = $matches;
        $attrs = $this->_get_attrs($attr);
        if (array_key_exists('name', $attrs) && !empty($this->disable_fields[$attrs['name']]) && empty($attrs['disable'])) $attrs['disable'] = 1;
        switch (strtoupper($type)) {
            case 'FORM':
                return $this->_parse_form($origtext, $attrs);
            case 'INPUT':
                return $this->_parse_input($origtext, $attrs);
            case 'SELECT':
                return $this->_parse_select($origtext, $attrs);
            case 'OPTION':
                return $this->_parse_option($origtext, $attrs);
            case 'TEXTAREA':
                return $this->_parse_textarea($origtext, $attrs);
        }
        return $origtext;
    }
    function _parse_form($text, &$attrs) {
        $this->object_param_cache = array();
        $this->current_form = array_key_exists('name', $attrs) ? $attrs['name'] : null;
        if (is_null($this->current_form)) $this->current_form = array_key_exists('id', $attrs) ? $attrs['id'] : null;
        if (!is_null($this->target)) {
            if (is_null($this->current_form) || $this->current_form != $this->target) {
                return $text;
            }
        }
        $text = preg_replace_callback('/<\s*(INPUT|SELECT|OPTION)\s+([^>]+)>/i', array(&$this, '_parse'), $text);
        $text = preg_replace_callback('/<\s*(TEXTAREA)\s+([^>]+)>.*?<\/\s*TEXTAREA\s*>/is', array(&$this, '_parse'), $text);
        return $text;
    }
    function _parse_input($text, &$attrs) {
        if (!array_key_exists('name', $attrs)) return $text;
        $type = array_key_exists('type', $attrs) ? strtolower($attrs['type']) : 'text';
        switch ($type) {
            case 'password':
                if (!$this->fill_password) break;
            case 'text':
            case 'search':
            case 'tel':
            case 'url':
            case 'email':
            case 'password':
            case 'datetime':
            case 'date':
            case 'month':
            case 'week':
            case 'time':
            case 'datetime-local':
            case 'number':
            case 'range':
            case 'color':
            case 'textfield':
            case 'hidden':
                $val = & $this->_get_param_one($attrs['name']);
                if (is_null($val)) break;
                $attrs['value'] = call_user_func($this->escape, $val);
                $text = '<input' . $this->_get_attrs_string($attrs) . '>';
                break;
            case 'radio':
                $val = & $this->_get_param_one($attrs['name']);
                if (is_null($val)) break;
                unset($attrs['checked']);
                if (strcmp($attrs['value'], call_user_func($this->escape, $val)) == 0) {
                    $attrs['checked'] = "checked";
                }
                $text = '<input' . $this->_get_attrs_string($attrs) . '>';
                break;
            case 'checkbox':
                $vals = & $this->_get_param($attrs['name']);
                if (is_null($vals)) break;
                if (!is_array($vals)) $vals = array($vals);
                unset($attrs['checked']);
                if (!array_key_exists('value', $attrs)) $attrs['value'] = 'on';
                foreach ($vals as $val) {
                    if (strcmp($attrs['value'], call_user_func($this->escape, $val)) == 0) {
                        $attrs['checked'] = "checked";
                        break;
                    }
                }
                $text = '<input' . $this->_get_attrs_string($attrs) . '>';
                break;
            }
            return $text;
        }
        function _parse_select($text, &$attrs) {
            $this->select['name'] = $attrs['name'];
            if (array_key_exists('multiple', $attrs)) $this->select['multiple'] = 1;
            return $text;
        }
        function _parse_option($text, &$attrs) {
            if (isset($this->select['multiple'])) {
                $vals = & $this->_get_param($this->select['name']);
                if (is_null($vals)) return $text;
                if (!is_array($vals)) $vals = array($vals);
                unset($attrs['selected']);
                foreach ($vals as $val) {
                    if (strcmp($attrs['value'], call_user_func($this->escape, $val)) == 0) {
                        $attrs['selected'] = "selected";
                        break;
                    }
                }
            } else {
                $val = & $this->_get_param_one($this->select['name']);
                if (is_null($val)) return $text;
                unset($attrs['selected']);
                if (strcmp($attrs['value'], call_user_func($this->escape, $val)) == 0) {
                    $attrs['selected'] = "selected";
                }
            }
            return '<option' . $this->_get_attrs_string($attrs) . '>';
        }
        function _parse_textarea($text, &$attrs) {
            $val = & $this->_get_param_one($attrs['name']);
            if (is_null($val)) return $text;
            return '<textarea' . $this->_get_attrs_string($attrs) . '>' . call_user_func($this->escape, $val) . '</textarea>';
        }
        function _get_attrs_string($attrs) {
            $ret = '';
            foreach ($attrs as $key => $val) {
                if ($key === '/') continue;
                $ret.= is_null($attrs[$key]) ? " $key=\"$key\"" : " $key=\"$val\"";
            }
            if (array_key_exists('/', $attrs)) $ret.= ' /';
            return $ret;
        }
        function _get_attrs($attr) {
            $this->attrs = array();
            $ret_attr = preg_replace_callback('/\s*(\w+)\s*=\s*("[^"]*"|\'[^\']*\'|\w+)\s*/', array(&$this, '_attrs_callback'), $attr);
            $attrs = $this->attrs;
            foreach (preg_split('/\s+/', $ret_attr, -1, PREG_SPLIT_NO_EMPTY) as $value) {
                $attrs[$value] = null;
            }
            return $attrs;
        }
        function _attrs_callback($m) {
            $this->attrs[strtolower($m[1]) ] = trim($m[2], '\'"');
            return '';
        }
        function _no_escape($str) {
            return $str;
        }
        function &_get_param_one($name) {
            $vals = & $this->_get_param($name);
            $val = is_array($vals) ? array_shift($vals) : $vals;
            return $val;
        }
        function &_get_param($name) {
            $null = null;
            if (array_key_exists($name, $this->ignore_fields)) return $null;
            if (array_key_exists($name, $this->fdat)) return $this->fdat[$name];
            if (array_key_exists($name, $this->object_param_cache)) return $this->object_param_cache[$name];
            foreach ($this->objects as $i => $dummy) {
                $v = $this->objects[$i]->param($name);
                if (is_null($v)) continue;
                if (is_array($v) && !count($v)) continue;
                $this->object_param_cache[$name] = $v;
                return $v;
            }
            return $null;
        }
    }
