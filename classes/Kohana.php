<?php

class Kohana extends Kohana_Core {

	public static function message($file, $path = NULL, $default = NULL)
	{
		static $messages;

		if ( ! isset($messages[$file]))
		{
			// Create a new message list
			$messages[$file] = array();

			if ($files = Kohana::find_file('messages', $file, 'yml'))
			{
				foreach ($files as $f)
				{
					// Combine all the messages recursively
          $yaml = (array) YAML::instance()->parse_file($f, TRUE);
					$messages[$file] = Arr::merge($messages[$file], $yaml);
				}
			} 
      elseif($files = Kohana::find_file('messages', $file))
			{
				foreach ($files as $f)
				{
					// Combine all the messages recursively
					$messages[$file] = Arr::merge($messages[$file], Kohana::load($f));
				}
			}
		}

		if ($path === NULL)
		{
			// Return all of the messages
			return $messages[$file];
		}
		else
		{
			// Get a message using the path
			return Arr::path($messages[$file], $path, $default);
		}
	}
}

