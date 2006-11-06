<?php

/**
 * General exception which might be raised within an application context
 *
 * @package framework
 * @author Saulius Rupainis <saulius@integry.net>
 */
class ApplicationException extends Exception 
{
			
	public function getFileTrace()
	{
		$showedFiles = array();
		$i = 0;
		foreach($this->getTrace() as $call)
		{
			if(isset($call['file']) && !in_array($call['file'], $showedFiles))
			{
				$showedFiles[] = $call['file'];
				echo ($i++).': '.$call['file'].'<br />';
			}
		}
	}
}

?>
