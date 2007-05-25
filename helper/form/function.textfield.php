<?php

/**
 * Renders text field
 *
 * If you wish to use autocomplete on a text field an additional parameter needs to be passed:
 *	
 * <code>
 *	  autocomplete="controller=somecontroller field=fieldname"
 * </code>
 *
 * The controller needs to implement an autoComplete method, which must return the AutoCompleteResponse 
 *
 * @param array $params
 * @param Smarty $smarty
 * @return string
 * 
 * @package application.helper
 * @author Integry Systems
 */
function smarty_function_textfield($params, $smarty) 
{
	$formParams = $smarty->_tag_stack[0][1];
	$handle = $formParams['handle'];
	$fieldName = $params['name'];

	if (!isset($params['id']))
	{
	  	$params['id'] = $params['name'];
	}
	
	if (!isset($params['type']))
	{
		$params['type'] = 'text';
	}
	
	// Check permissions
	if(isset($formParams['role']))
	{	
        ClassLoader::import('application.helper.AccessStringParser');
        if(!AccessStringParser::run($formParams['role']))
        {
            $params['readonly'] = 'readonly'; 
        }
	    unset($params['role']);
	}
	
	$content = '<input';
	foreach ($params as $name => $param) {
		$content .= ' ' . $name . '="' . $param . '"'; 
	}

	$content .= ' value="' . htmlspecialchars($handle->getValue($fieldName), ENT_QUOTES, 'UTF-8') . '"';
	$content .= '/>';

	if (isset($params['autocomplete']))
	{
	  	$acparams = array();
		foreach (explode(' ', $params['autocomplete']) as $param)
	  	{
			list($p, $v) = explode('=', $param, 2);
			$acparams[$p] = $v;
		}
		 
		$url = Router::getInstance()->createURL(array('controller' => $acparams['controller'], 
													  'action' => 'autoComplete', 
													  'query' => 'field=' . $acparams['field']));
		  
		$content .= '<span id="autocomplete_indicator_' . $params['id'] . '" class="progressIndicator" style="display: none;"></span>';
		$content .= '<div id="autocomplete_' . $params['id'] . '" class="autocomplete"></div>';
		$content .= '<script type="text/javascript">
						new Ajax.Autocompleter("' . $params['id'] . '", "autocomplete_' . $params['id'] . '", "' . $url . '", {frequency: 0.2, paramName: "' . $acparams['field'] . '", indicator: "autocomplete_indicator_' . $params['id'] . '"});
					</script>';
	}
	
	return $content;
}

?>