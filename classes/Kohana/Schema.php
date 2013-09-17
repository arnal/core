<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Schema {
  
  protected $_schemas = array();

  public function load_all()
  {
    $enabled = Kohana::$config->load('site.schemas');
    $schemas = array();
    foreach($enabled as $schema_name)
    {
      $schemas[$schema_name] = $this->load($schema_name);
    }
    return $schemas;
  }

  public function load($name)
  {
    if(isset($this->_schemas[$name]))
    {
      return $this->_schemas[$name];
    }

    // load hardcore from file
    $file = Kohana::find_file('schemas', $name, 'yml');
    if(!$file)
    {
      return FALSE;
    }
    
    $schema = YAML::instance()->parse_file($file);
    $this->_schemas[$name] = $this->extend($name, $schema);
    return $this->_schemas[$name];
  }

  public function extend($name, $schema)
  {
    $schema['id'] = $name;
    $schema['plural'] = Inflector::plural($name);
    $schema['home_url'] = URL::site($schema['plural']);
    $schema['wheres'] = isset($schema['wheres']) ? $schema['wheres'] : FALSE;
    $schema['groups'] = isset($schema['groups']) ? $schema['groups'] : FALSE;
    if($schema['groups'])
    {
      $schema['groups'][] = array('name' => 'Vše', 'code' => 'all', 'where' => array('id','>','0'));
    }
    $schema['allow_create'] = isset($schema['allow_create']) ? $schema['allow_create'] : TRUE;
    $schema['allow_delete'] = isset($schema['allow_delete']) ? $schema['allow_delete'] : TRUE;
    $schema['allow_edit'] = isset($schema['allow_edit']) ? $schema['allow_edit'] : TRUE;

    if(isset($schema['cols']))
    {
      foreach($schema['cols'] as $col_id => $col)
      {
        $schema['cols'][$col_id]['table'] = isset($col['table']) ? $col['table'] : TRUE;
      }
    }
    return $schema;
  }
}
