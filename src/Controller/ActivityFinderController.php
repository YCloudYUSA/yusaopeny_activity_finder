<?php

namespace Drupal\openy_activity_finder\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Site\Settings;
use Drupal\openy_activity_finder\Entity\ProgramSearchLog;
use Drupal\openy_activity_finder\OpenyActivityFinderBackendInterface;
use Drupal\openy_activity_finder\Entity\ProgramSearchCheckLog;
use Drupal\openy_activity_finder\OpenyActivityFinderSolrBackend;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * {@inheritdoc}
 */
class ActivityFinderController extends ControllerBase {

  // Cache queries for 5 minutes.
  const CACHE_LIFETIME = 300;

  /**
   * @var \Drupal\openy_activity_finder\OpenyActivityFinderBackendInterface
   */
  protected $backend;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Creates a new ActivityFinderController.
   */
  public function __construct(
    OpenyActivityFinderBackendInterface $backend,
    CacheBackendInterface $cacheBackend,
    TimeInterface $time,
    ImmutableConfig $config
  ) {
    $this->backend = $backend;
    $this->cacheBackend = $cacheBackend;
    $this->time = $time;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $config = $container->get('config.factory')->get('openy_activity_finder.settings');

    return new static(
      $container->get($config->get('backend')),
      $container->get('cache.default'),
      $container->get('datetime.time'),
      $config
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getData(Request $request) {
    $ip = $request->getClientIp();
    $user_agent = $request->headers->get('User-Agent', '');
    $hash_ip_agent = substr($user_agent, 0, 50) . '   ' . $ip;
    $record = [
      'hash_ip_agent' => $hash_ip_agent,
      'ages' => $request->query->get('ages'),
      'days' => $request->query->get('days'),
      'times' => $request->query->get('times'),
      'daystimes' => $request->query->get('daystimes'),
      'weeks' => $request->query->get('weeks'),
      'locations' => $request->query->get('locations'),
      'categories' => $request->query->get('categories'),
      'page' => $request->query->get('page'),
      'sort' => $request->query->get('sort'),
      'keywords' => $request->query->get('keywords'),
      'limit' => $request->query->get('limit'),
      'exclude' => $request->query->get('exclude'),
      'limitloc' => $request->query->get('limitloc'),
      'excludeloc' => $request->query->get('excludeloc'),
      'in_membership' => $request->query->get('in_membership'),
      'durations' => $request->query->get('durations'),
      'start_months' => $request->query->get('start_months'),
    ];
    $record['hash'] = md5(json_encode($record));

    $record_cache_key = $record;
    unset($record_cache_key['hash']);
    unset($record_cache_key['hash_ip_agent']);
    $cid = md5(json_encode($record_cache_key));

    if (!$this->config->get('disable_program_search_log')) {
      $log = ProgramSearchLog::create($record);
      $log->save();
      $log_id = $log->id();
    }
    else {
      $log_id = 0;
    }

    $parameters = $request->query->all();

    foreach ($parameters as &$value) {
      $value = urldecode($value);
    }

    $data = NULL;
    $debugMsg = 'Tried to get data from cache for get-data endpoint.';
    if ($cache = $this->cacheBackend->get($cid)) {
      $debugMsg .= " Result: hit, cid: $cid.";
      $data = $cache->data;
    }
    else {
      $data = $this->backend->runProgramSearch($parameters, $log_id);

      /** @var \Drupal\Core\Config\Config $expanderSectionsConfig */
      $expanderSectionsConfig = $this->config('openy_activity_finder.settings');
      $data['expanderSectionsConfig'] = $expanderSectionsConfig->getRawData();

      // Allow other modules to alter the search results.
      $this->moduleHandler()->alter('activity_finder_program_search_results', $data);

      // Cache for 5 minutes.
      $expire = $this->time->getRequestTime() + self::CACHE_LIFETIME;
      $debugMsg .= " Result: miss, cid: $cid.";
      $this->cacheBackend->set($cid, $data, $expire, [OpenyActivityFinderSolrBackend::ACTIVITY_FINDER_CACHE_TAG]);
      $debugMsg .= " Setting new cache, cid: $cid, expiration: $expire";
    }
    if (!$this->config->get('disable_cache_debug_log')) {
      \Drupal::logger('openy_activity_finder')->debug($debugMsg);
    }
    return new JsonResponse($data);
  }

  /**
   * Async click-logging endpoint used when bypass_register_redirect is on.
   *
   * Accepts a POST request with 'log_id' and 'details' parameters and records
   * a ProgramSearchCheckLog entry, mirroring what redirectToRegister() does
   * for the standard redirect flow. Returns JSON so JavaScript callers
   * (fetch / sendBeacon) get a well-formed response.
   */
  public function logRegister(Request $request): JsonResponse {
    if ($this->config->get('disable_program_search_log')) {
      return new JsonResponse(['status' => 'ok']);
    }

    $log = $request->request->get('log_id') ?? $request->query->get('log_id');
    $details = $request->request->get('details') ?? $request->query->get('details');

    if (!empty($details) && !empty($log)) {
      $details_log = ProgramSearchCheckLog::create([
        'details' => $details,
        'log_id' => $log,
        'type' => ProgramSearchCheckLog::TYPE_REGISTER,
      ]);
      $details_log->save();
    }

    return new JsonResponse(['status' => 'ok']);
  }

  /**
   * Redirect to register.
   */
  public function redirectToRegister(Request $request, $log) {
    $details = $request->query->get('details');
    $url = $request->query->get('url');

    if (empty($url)) {
      throw new NotFoundHttpException();
    }

    // Validate redirect request against trusted host patterns.
    $host_patterns = Settings::get('activity_finder_trusted_redirect_host_patterns', []);
    $trusted = FALSE;
    if (empty($host_patterns)) {
      $trusted = FALSE;
    }
    else {
      $host = parse_url($url, PHP_URL_HOST);
      foreach ($host_patterns as $host_pattern) {
        if (preg_match('/' . $host_pattern . '/i', $host)) {
          $trusted = TRUE;
          break;
        }
      }
    }

    if (!$trusted) {
      throw new NotFoundHttpException();
    }

    if (!empty($details) && !empty($log)) {
      $details_log = ProgramSearchCheckLog::create([
        'details' => $details,
        'log_id' => $log,
        'type' => ProgramSearchCheckLog::TYPE_REGISTER,
      ]);
      $details_log->save();
    }

    return new TrustedRedirectResponse($url, 301);
  }

  /**
   * Callback to retrieve programs full information.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function ajaxProgramsMoreInfo(Request $request) {
    $parameters = $request->query->all();
    $cid = md5(json_encode($parameters));
    $data = NULL;
    if ($cache = $this->cacheBackend->get($cid)) {
      $data = $cache->data;
    }
    else {
      $data = $this->backend->getProgramsMoreInfo($request);

      // Allow other modules to alter the search results.
      $this->moduleHandler()->alter('activity_finder_program_more_info', $data);

      // Cache for 5 minutes.
      $expire = $this->time->getRequestTime() + self::CACHE_LIFETIME;
      $this->cacheBackend->set($cid, $data, $expire, [OpenyActivityFinderSolrBackend::ACTIVITY_FINDER_CACHE_TAG]);
    }

    return new JsonResponse($data);
  }

}
