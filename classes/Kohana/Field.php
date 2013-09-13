<?php

class Kohana_Field {

  var $config = array();
  var $value = NULL;
  var $document = NULL;
  var $dt = NULL;

  public function __construct($value=NULL, $config=NULL)
  {
    if($value instanceOf Kohana_Document) 
    {
      $this->document = $value;
      if(isset($value->$config['id']))
      {
        $this->value = $value->get($config['id']);
      }
    }
    elseif($value !== NULL)
    {
      $this->value = $value;
    }

    if($config !== NULL)
    {
      $this->config = $config;
    }

    if(isset($this->config['dt']))
    {
      $this->dt = DT::factory($this->config['dt']);
      $this->dt->set_raw($this->value);
    }
  }

  public static function factory($value, $config)
  {
    return new self($value, $config);
  }

  public function input($name)
  {
    $output = NULL;

    $attrs = NULL;
    if(isset($this->config['lock']) AND $this->config['lock'] === TRUE)
    {
      $attrs = array('disabled' => 'disabled');
    }

    if($this->dt)
    {
      $output = $this->dt->input($name);
    }
    elseif(isset($this->config['type']) AND $this->config['type'] == 'fk')
    {
      $conf = Arnal::objects($name);

      $models = ORM::factory(ucfirst($name));
      list($rights, $models) = @Controller_Admin_Object::check_rights($conf, $models);

      $models = $models->find_all();
      $arr = array('' => '-- Nezadáno --');
      foreach($models as $m)
      {
        $arr[$m->id] = $m->title();
      }
      $real_col = $name.'_id';
      $output = Form::select($real_col, $arr, $this->document->get($real_col), $attrs);
    }
    elseif(isset($this->config['type']) AND $this->config['type'] == 'text')
    {
      $output = Form::textarea($name, $this->value, $attrs);
    }
    else
    {
      $output = Form::input($name, $this->value, $attrs);
    }
    return $output;
  }

  public function render()
  {
    $value = $this->value;
    $output = NULL;

    if($this->dt)
    {
      $output = $this->dt->render(TRUE);
    }
    elseif(isset($this->config['type']) AND $this->config['type'] == 'virtual')
    {
      $output = call_user_func(array($this->document, $this->config['id']));
    }
    elseif(isset($this->config['type']) AND $this->config['type'] == 'fk')
    {
      $real_col = $this->config['id'].'_id';
      if(is_object($value) AND $value->loaded())
      {
        $output = $value->anchor();
      }
      elseif(!empty($this->document->$real_col))
      {
        // neexistuje, takze napiseme puvodni ID a odkaz na log
        $oid = $this->document->$real_col;
        $output = '#'.$oid.' ('.HTML::anchor('logs?otype='.ucfirst($this->config['id']).'&oid='.urlencode('^'.$oid.'$'), 'log').')';
      }
    }
    else
    {
      $output = $value;
    }

    if(empty($output))
    {
      $output = '-';
    }
    elseif(isset($this->config['primary']) AND $this->config['primary'] == TRUE)
    {
      $output = '<strong>'.HTML::anchor($this->document->url(), $output).'</strong>';
    }
    return $output;
  }
}
