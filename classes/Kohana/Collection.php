<?php

class Kohana_Collection implements Iterator, Countable {

  var $model_name;
  var $driver;
  var $config;
  var $items = NULL;
  var $pos = 0;
  var $where = array();
  var $limit = NULL;
  var $offset = 0;
  var $order_by = NULL;
  var $current_group = NULL;
  var $filters_active = array();
  var $count_all = NULL;

  public function __construct($model_name, $options=NULL)
  {
    $this->model_name = $model_name;
    $this->config = Namlouvani::objects(strtolower($this->model_name));

    if($options !== NULL)
    {
      foreach($options as $o_key=>$o_val)
      {
        $this->$o_key = $o_val;
      }
    }
    if(!$this->config)
    {
      return FALSE;
    }
    $this->driver = ORM::factory($this->model_name);
  }

  public static function factory($collection_name, $options=NULL)
  {
    return new self($collection_name, $options);
  }
  
  public function rewind()
  {
    if($this->items === NULL)
    {
      $this->items = $this->process();
    }
    $this->pos = 0;
  }

  public function current()
  {
    return Document::factory($this->model_name, $this->items[$this->pos]);
  }

  public function key()
  {
    return $this->pos;
  }

  public function next()
  {
    ++$this->pos;
  }

  public function valid()
  {
    return isset($this->items[$this->pos]);
  }

  public function count_all()
  {
    if($this->count_all === NULL)
    {
      $this->count_all = $this->find(TRUE)->count_all();
    }
    return $this->count_all;
  }

  public function count()
  {
    if($this->items === NULL)
    {
      return $this->find()->count();
    }
    return count($this->items);
  }

  public function find($ignore_pager=FALSE)
  {
    $res = $this->driver;
    if(count($this->where) > 0)
    {
      foreach($this->where as $wa)
      {
        $res->where($wa[0], $wa[1], $wa[2]);
      }
    }

    if(!$ignore_page)
    {
      if($this->limit != NULL)
      {
        $res->limit($this->limit);
      }
      if($this->offset != 0)
      {
        $res->offset($this->offset);
      }
      if($this->order_by)
      {
        $res->order_by($this->order_by[0], $this->order_by[1]);
      }
      if(count($this->order_by) == 4)
      {
        $res->order_by($this->order_by[2], $this->order_by[3]);
      }
    }
    return $res;
  }

  public function process($res=NULL)
  {
    if(!$res)
    {
      $res = $this->find();
    }
    return $res->find_all();
  }

  public function process_groups($current_group=NULL)
  {
    $conf = $this->config;
    $args = array();
    if(isset($conf['groups']) AND $conf['groups'])
    {
      $i=0;
      foreach($conf['groups'] as $f)
      {
        if(($current_group == $f['code']) OR 
            ($current_group == NULL AND $i==0))
        {
          $this->current_group = $f['code'];
          $val = (substr($f['where'][2], 0, 1) == '@' ? DB::expr(substr($f['where'][2], 1)) : $f['where'][2]);
          $args[] = array($f['where'][0], $f['where'][1], $val);
          
          break;
        }
        $i++;
      }
    }
    $this->where = array_merge($this->where, $args);
    return $this->current_group;
  }
  
  public function process_filters($get=array())
  {
    $conf = $this->config;
    $args = array();
    if(isset($conf['wheres']) AND $conf['wheres'])
    {
      foreach($conf['wheres'] as $i=>$w)
      {
        if(isset($get[$w['code']]) AND !empty($get[$w['code']]))
        {
          if(substr($w['where'], 0, 1)=='@')
          {
            $w['where'] = DB::expr(substr($w['where'], 1));
          }
          $args[] = array($w['where'], 'REGEXP', trim($get[$w['code']]));
          $this->filters_active[$w['code']] = $get[$w['code']]; 
        }
      }
    }
    $this->where = array_merge($this->where, $args);
    return count($this->filters_active) > 0;
  }

  public function pagination()
  {
    $pagination = new Pagination(array('total_items' => $this->count_all(), 'items_per_page' => $this->limit));
    $this->offset = $pagination->offset;
    return $pagination;
  }

  public function render($type='table', $array=FALSE)
  {
    $conf = $this->config;
    $new_items = array();
    foreach($this as $doc)
    {
      $new_items[] = array('id'=>$doc->id, 'cols' => $doc->render($type, $array), 'url' => $doc->url(), 'type' => $doc->_type());
    }
    return $new_items;
  }
}

