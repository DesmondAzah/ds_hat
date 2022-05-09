<?php

namespace App\Domains;

use App\Traits\RequestTrait;
use Exception;
use Illuminate\Support\Facades\Cache;

/**
 * Base class for all Domain classes
 * @var string $baseUrl The base URL for requests to be made
 */
class Domain {

    use RequestTrait;
    protected $baseUrl;

    /**
     * Constructor for Domain classes. Initializes the $baseUrl to make requests
     * based on the domain name passed into the constructor.
     * @param string $domain_name The key of the domain entry in the config/domains.php file
     * @return void
     */
    protected function __construct($domain_name = null) {
        // get keys data from cache
        $key_list = Cache::get('s_keys');
        if (empty($domain_name))
            throw new Exception("Must specify a domain name");
        // get url from cache if cache exist, otherwise get from config domains file
        $url = !empty($key_list[$domain_name]['domain']) ? $key_list[$domain_name]['domain'] : config("domains.$domain_name");
        if (empty($url))
            throw new Exception("No domain configuration is available for $domain_name");
        if (env('APP_DEBUG') && env('APP_ENV') === 'local')
            $url = config("domains.$domain_name");
        $this->baseUrl = $url;
    }
}