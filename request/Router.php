<?php

ClassLoader::import("framework.request.Request");
ClassLoader::import("framework.request.Route");

/**
 * Router
 *
 * @author Saulius Rupainis <saulius@integry.net>
 * @package framework.request
 */
class Router
{
	/**
	 * Router instance (sigleton)
	 *
	 * @var Router
	 */
	private static $instance = null;

	/**
	 * The list of defined routes
	 *
	 * @var Route[]
	 */
	private $routeList = array();

	/**
	 * Default controller name
	 *
	 * @var string
	 */
	public static $defaultController = "index";

	/**
	 * Default action name
	 *
	 * @var string
	 */
	public static $defaultAction = "index";

	/**
	 * Application base dir
	 * E.x. www.example.com/myapplication/dir/ base dir is /myapplication/dir/
	 *
	 * @var string
	 */
	public static $baseDir = "";

	/**
	 * Base url
	 *
	 * @var string
	 */
	public static $baseUrl = "";

	/**
	 * Identifies if mod_rewrite is enabled
	 * Should be set manually by using enableURLRewrite(flag)
	 *
	 * @var bool
	 */
	private $isURLRewriteEnabled = true;

	private static $autoAppendVariableList = array();

	/**
	 * Router constructor
	 *
	 * @todo Add https and port to baseUrl
	 */
	private function __construct()
	{
		self::$baseDir = dirname($_SERVER['PHP_SELF']) . '/';
		self::$baseUrl = 'http://' . $_SERVER['SERVER_NAME'] . self::$baseDir;
	}

	/**
	 * Gets a base url
	 *
	 * @return string
	 */
	public function getBaseDir()
	{
		return self::$baseDir;
	}

	public function getBaseDirFromUrl()
	{
		$URI = $_SERVER['REQUEST_URI'];

		$queryStartPos = strpos($URI, '?');
		if ($queryStartPos !== false)
		{
			$URIBase = substr($URI, 0, $queryStartPos);
		}
		else
		{
			$URIBase = $URI;
		}

		$route = $this->getRequestedRoute();
		$virtualBaseDir = str_replace($route, "", $URIBase);

		return $virtualBaseDir;
	}

	/**
	 * Gets a base directory
	 *
	 * @return string
	 */
	public function getBaseUrl()
	{
		return self::$baseUrl;
	}

	/**
	 * Creates a singleton instance
	 *
	 * @return Router
	 */
	public static function getInstance()
	{
		if (self::$instance == null)
		{
			self::$instance = new Router();
		}
		return self::$instance;
	}


	/**
	 * Connects a new route to some URL pattern.
	 * URLPattern might have "variables", which has a ":" at the beggining. e.x. ":action"
	 *
	 * E.x.:
	 * <code>
	 * $router->connect(":controller/:action/:id", array(), array("id" => "[0-9]+"));
	 * </code>
	 *
	 * The route above will map to following URL's:
	 * item/add/34
	 * post/view/9233
	 *
	 * But not to these:
	 * item/add/AC331
	 * post/view
	 *
	 * URLPattern variables :controller :action :id by default might have value from
	 * a range [_.a-zA-Z0-9]. When you pass array("id" => "[0-9]") as varRequirements
	 * id is required to be only numeric.
	 *
	 *
	 * @link http://www.symfony-project.com/book/trunk/routing
	 *
	 * @param string $routeDefinitionPattern
	 * @param array $paramValueAssigments
	 * @param array $paramValueRequirements
	 *
	 */
	public function connect($routeDefinitionPattern, $paramValueAssigments = array(), $paramValueRequirements = array())
	{
		$this->routeList[] = new Route($routeDefinitionPattern, $paramValueAssigments, $paramValueRequirements);
	}

	/**
	 * Connects a new route to some URL pattern.
	 * URLPattern might have "variables", which has a ":" at the beggining. e.x. ":action"
	 *
	 * E.x.:
	 * <code>
	 * $router->connect(":controller/:action/:id", array(), array("id" => "[0-9]+"));
	 * </code>
	 *
	 * The route above will map to following URL's:
	 * item/add/34
	 * post/view/9233
	 *
	 * But not to these:
	 * item/add/AC331
	 * post/view
	 *
	 * URLPattern variables :controller :action :id by default might have value from
	 * a range [_.a-zA-Z0-9]. When you pass array("id" => "[0-9]") as varRequirements
	 * id is required to be only numeric.
	 *
	 *
	 * @link http://www.symfony-project.com/book/trunk/routing
	 *
	 * @param string $URLPattern
	 * @param array $defaultValueList
	 * @param array $varRequirements
	 *
	 */
	public function mapToRoute($URLStr, Request $request)
	{
		if (empty($URLStr) || !$this->isURLRewriteEnabled)
		{
			if (!$request->isValueSet("action"))
			{
				$request->setValue("action", self::$defaultAction);
			}
			if (!$request->isValueSet("controller"))
			{
				$request->setValue("controller", self::$defaultController);
			}
			return false;
		}

		foreach ($this->routeList as $route)
		{
			if (preg_match("/^" . $route->getRecognitionPattern() . "$/", $URLStr))
			{
				$URLParams = explode("/", $URLStr);
				$definitionPattern = $route->getDefinitionPattern();
				$definitionParts = explode("/", $definitionPattern);
				foreach ($definitionParts as $index => $part)
				{
					if ($route->isParam($part))
					{
						$paramName = substr($part, 1);
						$value = $URLParams[$index];
						$request->setValue($paramName, $value);
					}
				}
				$requestValueAssigments = $route->getRequestValueAssigments();
				foreach ($requestValueAssigments as $paramName => $value)
				{
					$request->setValue($paramName, $value);
				}
				return $route;
			}
		}
		throw new RouterException("Unable to map to any route");
	}

	/**
	 * Creates an URL by using a supplied URL param list
	 *
	 * @param array $URLParamList
	 * @return string
	 */
	public function createURL($URLParamList)
	{
		// merging persisted variables into an URL variable array
		$URLParamList = array_merge(self::$autoAppendVariableList, $URLParamList);

		$queryToAppend = "";
		if (!empty($URLParamList['query']))
		{
			$queryToAppend = "?" . $URLParamList['query'];
			unset($URLParamList['query']);
		}
		/* Handling special case: URL rewrite is not enabled */
		if (!$this->isURLRewriteEnabled)
		{
			return $this->createQueryString($URLParamList) . "&" . substr($queryToAppend, 1);
		}
		/* end */

		/* Handling special case: route to a default controller/action */
		if (!empty($URLParamList['controller'])
		           && $URLParamList['controller'] == self::$defaultController
		           && (empty($URLParamList['action']) || $URLParamList['action'] == self::$defaultAction)
		           && sizeof($URLParamList) <= 2)
		{
			return $this->getBaseDir() . $queryToAppend;
		}
		/* end */

		$matchingRoute = null;
		foreach ($this->routeList as $route)
		{
			$routeExpectedParamList = $route->getParamList();
			$routeParamsMatch = false;

			$routeParamNames = array_keys($routeExpectedParamList);
			$urlParamNames = array_keys($URLParamList);

			$urlParamDiff = array_diff($routeParamNames, $urlParamNames);
			$routeParamDiff = array_diff($urlParamNames, $routeParamNames);

			if ((sizeof($urlParamDiff)) == 0 && (sizeof($routeParamDiff) == 0))
			{
				foreach ($routeExpectedParamList as $paramName => $paramRequirement)
				{
					if (!isset($URLParamList[$paramName]) || !preg_match('/^' . $paramRequirement . '/' , $URLParamList[$paramName]))
					{
						$routeParamsMatch = false;
						break;
					}
					else
					{
						$routeParamsMatch = true;
					}
				}
			}
			if ($routeParamsMatch)
			{
				$matchingRoute = $route;
				break;
			}
		}
		if ($matchingRoute == null)
		{
			throw new RouterException("Router::createURL - Unable to find matching route <Br />" . 
									  var_export($URLParamList, true));
		}
		$url = $route->getDefinitionPattern();
		foreach ($URLParamList as $paramName => $value)
		{
			$url = str_replace(":" . $paramName, $value, $url);
		}


		return $this->getBaseDirFromUrl() . $url . $queryToAppend;
	}

	private function createQueryString($URLParamList)
	{
		$assigmentList = array();
		foreach ($URLParamList as $paramName => $value)
		{
			$assigmentList[] = $paramName . "=" . urlencode($value);
		}
		return "?" . implode("&", $assigmentList);
	}

	/**
	 *
	 *
	 * @param bool $status
	 */
	public function enableURLRewrite($status = true)
	{
		$this->isURLRewriteEnabled = $status;
	}

	/**
	 * Returns URL rewrite status
	 *
	 * @return bool
	 */
	public function isURLRewriteEnabled()
	{
		return $this->isURLRewriteEnabled;
	}

	/**
	 * Gets a request variable value containing front controllers route
	 *
	 * @return string
	 */
	public function getRequestedRoute()
	{
		if (!empty($_GET['route']))
		{
			return $_GET['route'];
		}
		else
		{
			return null;
		}
	}

	public function createUrlFromRoute($route)
	{
		return $this->getBaseDirFromUrl() . $route;
	}

	/**
	 * Set variable list that gets atonatically assigned when creating URL
	 * (self::createURL()) (there will be no need to assign such variables
	 * manually. E.x.current language code for a multilingual webapp)
	 *
	 * @param array $assocArray VariableName => VarValue
	 */
	public function setAutoAppendVariables($assocArray)
	{
		self::$autoAppendVariableList = $assocArray;
	}
}

?>