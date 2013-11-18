<?php

class Kohana_Document {

  var $_model_name;
  var $_model;
  var $_config;

  public function __construct($model_name, $data=NULL)
  {
    $this->_model_name = ucfirst($model_name);

    $type = strtolower($this->_model_name);
    $this->_config = Arnal::$schema->load($type);
    $this->_config['code'] = $type;

    if($data instanceOf Kohana_Model)
    {
      $this->_model = $data;
    }
    elseif(is_numeric($data))
    {
      $this->_model = ORM::factory($model_name, $data);
    }
    else
    {
      $this->_model = ORM::factory($model_name);
      if($data !== NULL)
      {
        $this->_model->values($data);
      }
    }
  }

  public static function factory($model_name, $data=NULL)
  {
    return new self($model_name, $data);
  }

  public function render($type='view', $as_array=FALSE, $conf=NULL)
  {
    $item = $this->_model;

    if($conf === NULL)
    {
      $conf = $this->_config;
    }

    $arr = array();
    foreach($conf['cols'] as $col_id => $col)
    {
      $col['id'] = $col_id;

      if($type == 'table' AND (isset($col['table']) AND $col['table']==FALSE))
      {
        continue;
      }
      elseif($type == 'edit' AND ($col_id == 'id' OR (isset($col['type']) AND $col['type'] == 'virtual')))
      {
        continue;
      }
      elseif($type == 'edit' AND isset($col['readonly']) AND $col['readonly'] == TRUE)
      {
        continue;
      }
      elseif($type == 'show' AND isset($col['show_hide']) AND $col['show_hide'])
      {
        continue;
      }
      elseif($type == 'show' AND preg_match('/^note/', $col_id))
      {
        continue;
      }
      elseif(isset($col['admin']) AND $col['admin'] ==  TRUE AND !Auth::instance()->get_user()->is_admin)
      {
        continue;
      }

      $field = Field::factory($this, $col);

      switch($type)
      {
        case 'edit':
          $col['input'] = $field->input($col_id);
          break;

        default:
          $col['rendered'] = isset($col['force_html']) && $col['force_html'] ? $field->render() : ($type == 'export' ? strip_tags($field->render()) : $field->render());
          $col['url'] = $this->url();
          $col['type'] = $conf['code'];
          break;
      }

      if($type == 'export')
      {
        $col['raw'] = $field->value;
      }

      $arr[] = $col;
    }

    if($as_array)
    {
      $simple = array();
      foreach($arr as $a)
      {
        $key = $a[$as_array[0]];
        $value = $a[$as_array[1]];
        $simple[$key] = $value; 
      }
      return $simple;
    }

    return $arr;
  }

  public function url()
  {
    return $this->_model->url();
  }

  public function __get($key)
  {
    return $this->get($key);
  }

  public function __set($key, $value)
  {
    return $this->set($key, $value);
  }

  public function __call($method, $args)
  {
    return call_user_func_array(array($this->_model, $method), $args);
  }

  /*public function offsetExists($offset)
  {
    return $this->_model->offsetExists($offset);
  }
  public function offsetGet($offset) { return $this->$offset; }
  public function offsetSet($offset, $value) {}
  public function offsetUnset($offset) {}*/

  public function __isset($key)
  {
      return $this->_model->__isset($key);
  }

  public function _type()
  {
    return $this->_model_name;
  }
}
