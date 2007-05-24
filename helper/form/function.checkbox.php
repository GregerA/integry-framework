<?php

/**
 * ...
 *
 * @param array $params
 * @param Smarty $smarty
 * @return string
 * 
 * @package application.helper
 * @author Saulius Rupainis <saulius@integry.net>
 */
function smarty_function_checkbox($params, $smarty) 
{    
    $formParams = $smarty->_tag_stack[0][1];
	$formHandler = $formParams['handle'];
	if (!($formHandler instanceof Form))
	{
		throw new HelperException('Element must be placed in {form} block');
	}
	$fieldName = $params['name'];
	
	if (!isset($params['id']))
	{
	  	$params['id'] = $params['name'];
	}
	
	if(!isset($params['value'])) 
    {
        $params['value'] = 1;    
    }	

	// Check permissions
	if(isset($formHandler['role']))
	{
        ClassLoader::import('framework.roles.AccessStringParser');
        if(!AccessStringParser::run($formHandler['role']))
        {
            $params['disabled'] = 'disabled'; 
        }
	    unset($params['role']);
	}
    
    $formValue = $formHandler->getValue($fieldName);
    	
    // get checkbox state if the form has been submitted
    if (1 == $formHandler->getValue('checkbox_' . $fieldName))
    {
        if ($formValue == $params['value'] || ('on' == $params['value'] && 1 == $formValue))
        {
            $params['checked'] = 'checked';
        }
        else
        {
            unset($params['checked']);
        }
    }
    else if ($params['value'] == $formValue)
    {
        $params['checked'] = 'checked';        
    }

	$output = '<input type="checkbox"';
	foreach ($params as $name => $value)
	{
		$output .= ' ' . $name . '="' . $value . '"';
	}
	    	
	$output .= '/><input type="hidden" name="checkbox_' . $params['name'] . '" value="1" />';
		
	return $output;
}

?>