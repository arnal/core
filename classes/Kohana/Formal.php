<?php

class Kohana_Formal {

  var $conf;
  var $form_name;

  public static function factory($form_name)
  {
    return new self($form_name);
  }

  public function __construct($form_name)
  {
    $this->form_name = $form_name;
    $this->conf = (array) Kohana::$config->load('forms')->get($form_name);
  }

  public function _validator($data)
  {
    $output = array();
    $validator = Validation::factory($data);
    foreach($this->conf['keys'] as $k => $k_obj)
    {
      if(isset($k_obj['require']) AND $k_obj['require'])
      {
        $validator->rule($k, 'not_empty');
      }
      if(isset($k_obj['rules']) AND is_array($k_obj['rules']))
      {
        foreach($k_obj['rules'] as $rule)
        {
          if(is_array($rule))
          {
            foreach($rule as $rule_key => $rule_val)
            {
              $validator->rule($k, $rule_key, array(':value', $rule_val));
            }
          }
          else
          {
            $validator->rule($k, $rule);
          } 
        }
      }
    }
    return $validator;
  }

  public function _output_data($data)
  {
    $output = array();
    foreach($this->conf['keys'] as $k => $k_obj)
    {
      if(isset($k_obj['pass']) AND $k_obj['pass'] === FALSE)
      {
        continue;
      }
      $output[$k] = $data[$k];
    }
    return $output;
  }

  public function submit($data)
  {
    $validator = $this->_validator($data);
    $output_data = $this->_output_data($validator->as_array());

    if($validator->check())
    {
      return array(TRUE, NULL, $output_data);
    }

    return array(FALSE, $validator->errors('forms/'.$this->form_name), $output_data);
  }

}
