<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Arnal {
    
  const VERSION = '1.0.0';
  const CODENAME = 'kaja';

  public static function msg($msg, $type='success')
  {
    $_SESSION['msg'] = array('type' => $type, 'msg' => $msg);
    return TRUE;
  }

  public static function log($txt, $data=array(), $object_type=NULL, $object_id=NULL)
  {
    $log = ORM::factory('Log');
    $log->created_at = date('Y-m-d H:i', time());
    $log->text = $txt;
    $log->user_id = Auth::instance()->get_user()->id;
    $log->data = json_encode($data);
    $log->object_type = $object_type;
    $log->object_id = $object_id;
    $log->ip_addr = @$_SERVER['REMOTE_ADDR'];
    $log->useragent = @$_SERVER['HTTP_USER_AGENT'];
    return $log->save();
  }

  public static function email($to, $template_code, $args)
  {
    $site_config = Kohana::$config->load('site')->as_array();

    $template = ORM::factory('Emailtemplate');
    $template->where('code','=',$template_code);
    $template = $template->find();

    if(!$template->loaded())
    {
      return FALSE;
    }
    
    require_once MODPATH.'twig/vendor/twig/lib/Twig/Autoloader.php';
    Twig_Autoloader::register();

    $loader = new Twig_Loader_String();
    $twig = new Twig_Environment($loader);

    $body_html = $twig->render($template->text, @$args['data']);
    $subject = $twig->render($template->subject, @$args['data']);

    $body = strip_tags($body_html);
    $from = $site_config['email']; 
    $from_name = $site_config['title'];
    $email = Email::factory($subject, $body)
        ->to($to)
        ->from($from, $from_name);

    $email->message($body_html, 'text/html');
    $email->send();

    // log
    $mail_log = ORM::factory('Mail');
    $mail_log->from = $from;
    $mail_log->to = $to;
    $mail_log->subject = $subject;
    $mail_log->text = $body_html;
    $mail_log->emailtemplate_id = $template->id;
    if(isset($args['submit_id']))
    {
      $mail_log->submit_id = $args['submit_id'];
    }
    $mail_log->created_at = Date('Y-m-d H:i:s');
    $mail_log->save();

    return $mail_log->id;
  }

  public static function objects($id=NULL)
  {
    $objects = (array) Kohana::$config->load('objects');
    foreach($objects as $o_id => $o)
    {
      $plural = Inflector::plural($o_id);
      $objects[$o_id]['plural'] = $plural;
      $objects[$o_id]['home_url'] = URL::site($plural);
      $objects[$o_id]['id'] = $o_id;
      $objects[$o_id]['wheres'] = isset($o['wheres']) ? $o['wheres'] : FALSE;
      $objects[$o_id]['groups'] = isset($o['groups']) ? $o['groups'] : FALSE;
      if($objects[$o_id]['groups'])
      {
        $objects[$o_id]['groups'][] = array('name' => 'Vše', 'code' => 'all', 'where' => array('id','>','0'));
      }
      $objects[$o_id]['allow_create'] = isset($o['allow_create']) ? $o['allow_create'] : TRUE;
      $objects[$o_id]['allow_delete'] = isset($o['allow_delete']) ? $o['allow_delete'] : TRUE;
      $objects[$o_id]['allow_edit'] = isset($o['allow_edit']) ? $o['allow_edit'] : TRUE;

      if(isset($o['cols']))
      {
        foreach($o['cols'] as $col_id => $col)
        {
          $objects[$o_id]['cols'][$col_id]['table'] = isset($col['table']) ? $col['table'] : TRUE;
        }
      }
      if($id == $o_id)
      {
        return $objects[$o_id];
      }
    }
    return $objects;
  }

  public static function objects_plural_route()
  {
    $out = array();
    foreach(self::objects() as $o_id => $o)
    {
      $out[] = $o['plural'];
    }
    $str = '('.join('|', $out).')';
    return $str;
  }

  public static function objects_route()
  {
    return '('.join('|',array_keys(self::objects())).')';
  }

  public static function object_config($type_plural)
  {
    foreach(self::objects() as $o_id => $o)
    {
      if($o['plural'] == $type_plural)
      {
        return self::objects($o_id);
      }
    }
    return FALSE;
  }
}
