<?php
/**
 * @package php-tmdb\laravel
 * @author Mark Redeman <markredeman@gmail.com>
 * @copyright (c) 2014, Mark Redeman
 */
namespace Tmdb\Laravel;

use Illuminate\Support\ServiceProvider;
use Tmdb\Event\BeforeRequestEvent;
use Tmdb\Event\RequestEvent;
use Tmdb\Laravel\TmdbServiceProviderLaravel4;
use Tmdb\Laravel\TmdbServiceProviderLaravel;
use Tmdb\Token\Api\ApiToken;
use Tmdb\Client;
use Tmdb\Event\Listener\Request\AcceptJsonRequestListener;
use Tmdb\Event\Listener\Request\ApiTokenRequestListener;
use Tmdb\Event\Listener\Request\ContentTypeJsonRequestListener;
use Tmdb\Event\Listener\Request\UserAgentRequestListener;
use Tmdb\Event\Listener\RequestListener;
use Tmdb\Token\Api\BearerToken;


class TmdbServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Actual provider
     *
     * @var \Illuminate\Support\ServiceProvider
     */
    protected $provider;

    /**
     * Construct the TMDB service provider
     */
    public function __construct()
    {
        // Call the parent constructor with all provided arguments
        $arguments = func_get_args();
        call_user_func_array(
            [$this, 'parent::' . __FUNCTION__],
            $arguments
        );

        $this->registerProvider();
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        return $this->provider->boot();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Configure any bindings that are version dependent
        $this->provider->register();

        // Let the IoC container be able to make a Symfony event dispatcher
        $this->app->bind(
            'Symfony\Component\EventDispatcher\EventDispatcherInterface',
            'Symfony\Component\EventDispatcher\EventDispatcher'
        );

        // Setup default configurations for the Tmdb Client
        $this->app->singleton('Tmdb\Client', function() {
            $config = $this->provider->config();
            $options = $config['options'];

            $ed = $this->app->make('Tmdb\Laravel\Adapters\EventDispatcherAdapter');

            // Use an Event Dispatcher that uses the Laravel event dispatcher
            $options['event_dispatcher']['adapter'] = $ed;

            if (($config['bearer_token'] ?? '') !== '') {
                $options['api_token'] = new BearerToken($config['bearer_key']);
            } else if (($config['api_key'] ?? '') !== '') {
                $options['api_token'] = new ApiToken($config['api_key']);
            } else {
                throw new \RuntimeException("php-tmdb-laravel requires bearer_token or api_key");
            }


            unset($options['log']);
            unset($options['cache']);

            $client = new Client($options);

            /**
             * Required event listeners and events to be registered with the PSR-14 Event Dispatcher.
             */
            $requestListener = new RequestListener($client->getHttpClient(), $ed);
            $ed->addListener(RequestEvent::class, $requestListener);

            $apiTokenListener = new ApiTokenRequestListener($client->getToken());
            $ed->addListener(BeforeRequestEvent::class, $apiTokenListener);

            $acceptJsonListener = new AcceptJsonRequestListener();
            $ed->addListener(BeforeRequestEvent::class, $acceptJsonListener);

            $jsonContentTypeListener = new ContentTypeJsonRequestListener();
            $ed->addListener(BeforeRequestEvent::class, $jsonContentTypeListener);

            $userAgentListener = new UserAgentRequestListener();
            $ed->addListener(BeforeRequestEvent::class, $userAgentListener);

            return $client;
        });

        // bind the configuration (used by the image helper)
        $this->app->bind('Tmdb\Model\Configuration', function() {
            $configuration = $this->app->make('Tmdb\Repository\ConfigurationRepository');
            return $configuration->load();
        });
    }

    /**
     * Register the ServiceProvider according to Laravel version
     *
     * @return \Tmdb\Laravel\Provider\ProviderInterface
     */
    private function registerProvider()
    {
        $app = $this->app;

        // Pick the correct service provider for the current verison of Laravel
        $this->provider = new TmdbServiceProviderLaravel($app);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('tmdb');
    }
}
