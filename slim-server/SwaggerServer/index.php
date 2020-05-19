<?php
/**
 * PrefixCommons API
 * @version 0.1.0
 */

require_once __DIR__ . '/vendor/autoload.php';
date_default_timezone_set('UTC');
$version = "/v1";

$configuration = [
    'settings' => [
        'displayErrorDetails' => true,
    ],
];
$c = new \Slim\Container($configuration);
$app = new \Slim\App($c);

$c = $app->getContainer();
$c['notAllowedHandler'] = function ($c) {
    return function ($request, $response, $methods) use ($c) {
        return $c['response']
            ->withStatus(405)
            ->withHeader('Allow', implode(', ', $methods))
            ->withHeader('Content-type', 'text/html')
            ->write('Method must be one of: ' . implode(', ', $methods));
    };
};
unset($app->getContainer()['errorHandler']);

$logfile = "my.log";
$logger = Elasticsearch\ClientBuilder::defaultLogger($logfile);
$client = Elasticsearch\ClientBuilder::create()
	->setLogger($logger)  
	->setHosts( ['elastic:9200'] )
	->build();
$params = [
	'index' => 'prefixcommons',
	'type' => 'item'
];

# return empty. 
$app->GET('/', function($request, $response, $args) {   
	$response->write('No result');
	return $response;
});


/**
 * GET getResources
 * Summary: Resources
 * Notes: Get a list of all the resources in the repository.  The response includes the identifier and display name for each resource 
 * Output-Formats: [application/json]
 */
$app->GET($version.'/resources', function($request, $response, $args)  {
	global $client, $params;

	$params = [
		'body' => [
			"fields" => ["preferredPrefix", "title"],
			'query' => [
				'match_all' => []
			]
		]
	];
	
	try {
		$results = $client->search($params);
		$myresults = \renderResults($results, $params);
		$response->write(json_encode($myresults,JSON_PRETTY_PRINT));
		$response->withStatus(200);
	} catch(Exception $e) {
		$response->withStatus(500);
	}
	return $response;
});



/**
 * GET getResourceByPrefix
 * Summary: Resource
 * Notes: Get one or more resources that match a prefix
 * Output-Formats: [application/json]
 */
$app->GET($version.'/resource/byPrefix/[{prefix}]', function($request, $response, $arg) {
	global $client, $params;

	$arg_name = 'prefix';
	if(null === ($arg_value = \getArg($request->getQueryParams(), $arg, $arg_name))) {
		$response->write("Please provide a $arg_name to search with!");
		return $response;
	}
	
	$params = [
		'body' => [
			'fields' => ["preferredPrefix", "alternativePrefix","title"],
			'query' => [
				'filtered' => [
					'filter' => [
						'or' => [
							['term' => [ "preferredPrefix" => "$arg_value" ]],
							['term' => [ "alternativePrefix" => "$arg_value" ]]
						]
					]
				]
			]
		]
	];
	
	try {
		$results = $client->search($params);
		$myresults = \renderResults($results, $params);
		$response->write(json_encode($myresults,JSON_PRETTY_PRINT));
		$response->withStatus(200);
	} catch(Exception $e) {
		$response->withStatus(500);
	}
	return $response;
});




/**
 * GET getResourcesByOrganization
 * Summary: Resource
 * Notes: Get one or more resources that match an organization
 * Output-Formats: [application/json]
 */
$app->GET($version.'/resource/byOrganization[/{organization}]', function($request, $response, $arg) {
	global $client, $params;

	$arg_name = 'organization';
	if(null === ($arg_value = \getArg($request->getQueryParams(), $arg, $arg_name))) {
		$response->write("Please provide a $arg_name to search with!");
		return $response;
	}
	
	$params = [
		'body' => [
			'fields' => ["preferredPrefix", "alternativePrefix","title"],
			'query' => [
				'filtered' => [
					'filter' => 
							['term' => [ "organization" => "$arg_value" ]],
				]
			]
		]
	];
	
	try {
		$results = $client->search($params);
		$myresults = \renderResults($results, $params);
		$response->write(json_encode($myresults,JSON_PRETTY_PRINT));
		$response->withStatus(200);
	} catch(Exception $e) {
		$response->withStatus(500);
	}
	return $response;
});




/**
 * GET getOrganizations
 * Summary: Get a list of all organizations that provide a resource
 * Notes: 
 * Output-Formats: [application/json]
 */
$app->GET($version.'/organizations', function($request, $response, $args) {
	global $client, $params;
	$params = [
		'body' => [
			'fields' => ["organization"],
			'query' => [
				'match_all' => []
			]
		]
	];
	
	try {
		$results = $client->search($params);
		$myresults = \renderResults($results, $params);
		$response->write(json_encode($myresults,JSON_PRETTY_PRINT));
		$response->withStatus(200);
	} catch(Exception $e) {
		$response->withStatus(500);
	}
	return $response;
});


/**
 * GET getPreferredCURIE
 * Summary: Generate the preferred CURIE from a CURIE -> prefix:local_identifier
 * Notes: 
 * Output-Formats: [application/json]
 */
$app->GET($version.'/getPreferredCURIE/{curie}', function($request, $response, $arg) {
	global $client, $params;
	
	$arg_name = 'curie';
	if(null === ($arg_value = \getArg($request->getQueryParams(), $arg, $arg_name))) {
		$response->write("Please provide a $arg_name");
		return $response;
	}
	
	if(FALSE === strstr($arg_value, ":")) {
		$response->write("CURIE does not contain a ':'");
		return $response;
	}
	list($prefix,$local_id) = explode(':',$arg_value);
	
	$params = [
		'body' => [
			'fields' => ["preferredPrefix"],
			'query' => [
				'filtered' => [
					'filter' => [
						'or' => [
							['term' => [ "preferredPrefix" => "$prefix" ]],
							['term' => [ "alternativePrefix" => "$prefix" ]]
						]
					]
				]
			]
		]
	];
	
	try {
		$results = $client->search($params);
		if($results['hits']['total'] > 0) {
			foreach($results['hits']['hits'] AS $hit) {
				if(isset($hit['fields'])) {
					foreach($hit['fields'] AS $k => $v) {
						$preferredPrefix = $v[0];
						break;
					}
			}}
		}
		$curie = $preferredPrefix.":".$local_id;
		$myresults = [
			'info' => [
				'query' => 'getPreferredCURIE/'.$arg_value,
				'finished_at' => date(DATE_ATOM),
				'time_elapsed' => $results['took'],
				'number_of_hits' => 1
			],
			'results' => [
				'curie' => $curie
			]
		];
	
		$response->write(json_encode($myresults,JSON_PRETTY_PRINT));
		$response->withStatus(200);
	} catch(Exception $e) {
		$response->withStatus(500);
	}	
	return $response;
});


/**
 * GET getPreferredURI
 * Summary: Generate the preferred URI from a URI
 * Notes: 
 * Output-Formats: [application/json]
 */
$app->GET($version.'/getPreferredURI/{uri:.*}', function($request, $response, $arg) {
	global $client, $params;

	$arg_name = 'uri';
	if(null === ($arg_value = \getArg($request->getQueryParams(), $arg, $arg_name))) {
		$response->write("Please provide a $arg_name");
		return $response;
	}

	$base_uri = $identifier = '';
	if(false === \parseURI($arg_value, $base_uri, $identifier)) {
		$response->write("Unable to parse URI");
		return $response;
	}
	
	$params = [
		'body' => [
			'fields' => ["preferredBaseURI"],
			'query' => [
				'filtered' => [
					'filter' => [
						'or' => [
							['term' => [ "preferredBaseURI" => "$base_uri" ]],
							['term' => [ "alternativeBaseURI" => "$base_uri" ]]
						]
					]
				]
			]
		]
	];
	
	try {
		$results = $client->search($params);
		if($results['hits']['total'] > 0) {
			foreach($results['hits']['hits'] AS $hit) {
				if(isset($hit['fields'])) {
					foreach($hit['fields'] AS $k => $v) {
						$preferredBaseURI = $v[0];
						break;
					}
			}}
		}
		
		if(!isset($preferredBaseURI)) {
			$myresults = [
				'info' => [
					'query' => 'getPreferredURI/'.$arg_value,
					'finished_at' => date(DATE_ATOM),
					'time_elapsed' => $results['took'],
					'number_of_hits' => 0
			]];
		} else {
			$myresults = [
				'info' => [
					'query' => 'getPreferredURI/'.$arg_value,
					'finished_at' => date(DATE_ATOM),
					'time_elapsed' => $results['took'],
					'number_of_hits' => 1
				],
				'results' => [
					'uri' => $preferredBaseURI.$identifier
				]
			];
		}
		$response->write(json_encode($myresults,JSON_PRETTY_PRINT));
		$response->withStatus(200);	
	} catch(Exception $e) {
		$response->withStatus(500);
	}
	
	return $response;
});


/**
 * GET getPreferredURIfromProvider
 * Summary: Generate the preferred URI from a specific provider
 * Notes: 
 * Output-Formats: [application/json]
 */
$app->GET($version.'/getPreferredURIfromProvider/{provider}/{uri:.*}', function($request, $response, $arg) {
	global $client, $params;

	$arg_name = 'provider';
	if(null === ($arg_value = \getArg($request->getQueryParams(), $arg, $arg_name))) {
		$response->write("Please provide a $arg_name");
		return $response;
	}
	$provider = $arg_value;
	
	$arg_name = 'uri';
	if(null === ($arg_value = \getArg($request->getQueryParams(), $arg, $arg_name))) {
		$response->write("Please provide a $arg_name");
		return $response;
	}
	
	$base_uri = $identifier = '';
	if(false === \parseURI($arg_value, $base_uri, $identifier)) {
		$response->write("Unable to parse URI");
		return $response;
	}
	
	# find the uri template that matches
	$params = [
		'body' => [
			'fields' => ["preferredBaseURI"],
			'query' => [
				'filtered' => [
					'filter' => [
						'or' => [
							['term' => [ "preferredBaseURI" => "$base_uri" ]],
							['term' => [ "alternativeBaseURI" => "$base_uri" ]]
						]
					]
				]
			]
		]
	];
	
	try {
		$results = $client->search($params);
		if($results['hits']['total'] > 0) {
			foreach($results['hits']['hits'] AS $hit) {
				if(isset($hit['fields'])) {
					foreach($hit['fields'] AS $k => $v) {
						$preferredBaseURI = $v[0];
						break;
					}
			}}
		}
		
		
		# next, find the template for the specified provider 
		
		
		if(!isset($preferredBaseURI)) {
			$myresults = [
				'info' => [
					'query' => 'getPreferredURI/'.$arg_value,
					'finished_at' => date(DATE_ATOM),
					'time_elapsed' => $results['took'],
					'number_of_hits' => 0
			]];
		} else {
			$myresults = [
				'info' => [
					'query' => 'getPreferredURI/'.$arg_value,
					'finished_at' => date(DATE_ATOM),
					'time_elapsed' => $results['took'],
					'number_of_hits' => 1
				],
				'results' => [
					'uri' => $preferredBaseURI.$identifier
				]
			];
		}
		$response->write(json_encode($myresults,JSON_PRETTY_PRINT));
		$response->withStatus(200);	
	} catch(Exception $e) {
		$response->withStatus(500);
	}
	
	return $response;
});



function renderResults($results, $params) {
	$myresults = [
		'info' => [
			'query' => $params,
			'finished_at' => date(DATE_ATOM),
			'time_elapsed' => $results['took'],
			'number_of_hits' => $results['hits']['total']
		]
	];
	if($results['hits']['total'] > 0) {
		foreach($results['hits']['hits'] AS $hit) {
			if(isset($hit['fields'])) {
				$myresults['results'][] = $hit['fields'];
		}}
	}
	return $myresults;
};

function getArg($queryParams, $args, $arg_name)
{
	if(!isset($args[$arg_name]) and (!isset($queryParams[$arg_name]) or !$queryParams[$arg_name])) {
		return null;
	}
	if( isset($args[$arg_name])) $result = $args[$arg_name];
	else $result = $queryParams[$arg_name];
	return $result;
}

function parseURI($uri, &$base_uri, &$identifier) 
{
	if(FALSE === ($pos = strrpos($uri, "#"))) {
		if(FALSE === ($pos = strrpos($uri, "/"))) {
			return false;
		}
	}
	if($pos !== FALSE) {
		$base_uri = substr($uri, 0, $pos+1);
		$identifier = substr($uri, $pos+1);
		return true;
	} 
	return false;
}

$app->run();
