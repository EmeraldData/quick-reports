<?php 

class openilsConfig {
	
	public function __construct() {

		$xml=simplexml_load_file(QR_OPENSRF_XML_PATH);
		if (! $xml) {
			new displayMessageView('Error parsing openils.xml.');
			die;
		}
		
		if (QR_PGSQL_USE_OPENSRF_XML_CONFIG) {		
			define ('QR_PGSQL_HOST', $xml->default->reporter->setup->state_store->host);
			define ('QR_PGSQL_PORT', $xml->default->reporter->setup->state_store->port);
			define ('QR_PGSQL_DBNAME', $xml->default->reporter->setup->state_store->db);
			define ('QR_PGSQL_USER', $xml->default->reporter->setup->state_store->user);
			define ('QR_PGSQL_PASSWORD', $xml->default->reporter->setup->state_store->pw);
		}

		if (QR_MEMCACHE_USE_OPENSRF_XML_CONFIG) {
			$pos = strpos($xml->default->cache->global->servers->server[0], ':');
			$host = substr($xml->default->cache->global->servers->server[0], 0, $pos);
			$port = substr($xml->default->cache->global->servers->server[0], $pos+1);
			define ('QR_MEMCACHE_HOST_1', $host);
			define ('QR_MEMCACHE_PORT_1', $port);
			if (isset($xml->default->cache->global->servers->server[1])) {
				$pos = strpos($xml->default->cache->global->servers->server[1], ':');
				$host = substr($xml->default->cache->global->servers->server[1], 0, $pos);
				$port = substr($xml->default->cache->global->servers->server[1], $pos+1);
				define ('QR_MEMCACHE_HOST_2', $host);
				define ('QR_MEMCACHE_PORT_2', $port);
			}
		}
	}
		   
}
?>
