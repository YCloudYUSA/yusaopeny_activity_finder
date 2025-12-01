<?php

namespace Drupal\openy_activity_finder\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\openy_activity_finder\OpenyActivityFinderSolrBackend;
use Drupal\openy_system\EntityBrowserFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Activity Finder' block.
 *
 * @Block(
 *   id = "activity_finder_4",
 *   admin_label = @Translation("Activity Finder"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class ActivityFinder4Block extends BlockBase implements ContainerFactoryPluginInterface {

  use EntityBrowserFormTrait;

  /**
   * Config Factory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Block object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The Config Factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              ConfigFactory $config_factory,
                              EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => 'visible',
      'limit_by_category_daxko' => [],
      'limit_by_category' => [],
      'exclude_by_category' => [],
      'limit_by_location' => [],
      'exclude_by_location' => [],
      'legacy_mode' => 0,
      'weeks_filter' => 0,
      'hide_home_branch_block' => 0,
      'hide_activities_filter' => 0,
      'background_image' => NULL,
      'in_memberships_filter' => 0,
      'duration_filter' => 0,
      'start_month_filter' => 0,
      'skip_wizard' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    [$activity_finder_settings, $backend_service_id, $backend] = $this->getBackend();
    $conf = $this->getConfiguration();

    $image_mobile = '';
    $image_desktop = '';
    /** @var \Drupal\media\MediaInterface $media */
    if (!empty($conf['background_image']) && $media = static::loadEntityBrowserEntity($conf['background_image'])) {
      $image = $media->field_media_image->entity;
      $storage = $this->entityTypeManager->getStorage('image_style');
      $image_mobile = $storage->load('prgf_banner')->buildUrl($image->getFileUri());
      $image_desktop = $storage->load('prgf_gallery')->buildUrl($image->getFileUri());
    }

    $limit_by_category = $conf['limit_by_category'];
    $limit_by_location = $conf['limit_by_location'];

    if ($backend_service_id == "openy_daxko2.openy_activity_finder_backend") {
      $limit_by_category = $conf['limit_by_category_daxko'] ? explode(', ', $conf['limit_by_category_daxko']) : [];
    }

    $activities = $backend->getCategories();

    // Remove empty programs and subprograms.
    $results = $backend->runProgramSearch([], 0);

    $facets = [];
    if (!empty($results['facets']['field_activity_category'])) {
      $facets = $results['facets']['field_activity_category'];
    }

    $activeSubPrograms = [];
    if ($facets) {
      foreach ($facets as $item) {
        if (isset($item['id']) && !empty($item['id'])) {
          $activeSubPrograms[] = $item['id'];
        }
      }
    }
    foreach ($activities as $indexProgram => $program) {
      if (isset($program['value'])) {
        foreach ($program['value'] as $indexSubProgram => $subProgram) {
          if (!in_array($subProgram['value'], $activeSubPrograms) ||
            ($limit_by_category && !in_array($subProgram['value'], $limit_by_category))) {
            unset($activities[$indexProgram]['value'][$indexSubProgram]);
          }
        }
      }
    }
    foreach ($activities as $indexProgram => $program) {
      if (empty($program['value'])) {
        unset($activities[$indexProgram]);
      }
    }

    // Sort activity groups and activities in alphabetical order.
    usort($activities, function ($a, $b) {
      return $a['label'] <=> $b['label'];
    });
    foreach ($activities as &$activity) {
      usort($activity['value'], function ($a, $b) {
        return $a['label'] <=> $b['label'];
      });
    }

    $sort_options = $backend->getSortOptions();

    $locations = array_values($backend->getLocations());
    // Filter out excluded locations.
    foreach ($locations as $indexType => $type) {
      if (isset($type['value'])) {
        foreach ($type['value'] as $indexLocation => $location) {
          if ($limit_by_location && !in_array($location['value'], $limit_by_location)) {
            unset($locations[$indexType]['value'][$indexLocation]);
          }
        }
      }
    }
    // Remove empty location groups.
    foreach ($locations as $indexType => $type) {
      if (empty($type['value'])) {
        unset($locations[$indexType]);
      }
    }

    \Drupal::moduleHandler()->alter('activity_finder_location_list', $locations);
    return [
      '#theme' => 'openy_activity_finder_4_block',
      '#backend_service' => $backend_service_id,
      '#label' => $conf['label'],
      '#label_display' => $conf['label_display'] == 'visible',
      '#ages' => $backend->getAges(),
      '#days' => $backend->getDaysOfWeek(),
      '#times' => $backend->getPartsOfDay(),
      '#days_times' => $backend->getDaysTimes(),
      '#start_months' => $backend->getStartMonths(),
      '#durations' => $backend->getDurations(),
      '#weeks' => $backend->getWeeks(),
      '#categories' => $backend->getCategories(),
      '#categories_type' => $backend->getCategoriesType(),
      '#activities' => $activities,
      '#locations' => $locations,
      '#disable_search_box' => (bool) $activity_finder_settings->get('disable_search_box'),
      '#disable_spots_available' => (bool) $activity_finder_settings->get('disable_spots_available'),
      '#sort_options' => $sort_options,
      // @todo make default sort option configurable.
      '#default_sort_option' => array_keys($sort_options)[0],
      '#relevance_sort_option' => $backend->getRelevanceSort(),
      '#filters_section_config' => $backend->getFiltersSectionConfig(),
      '#limit_by_category' => $limit_by_category,
      '#exclude_by_category' => $conf['exclude_by_category'],
      '#limit_by_location' => $conf['limit_by_location'] ?? [],
      '#exclude_by_location' => $conf['exclude_by_location'],
      '#legacy_mode' => (bool) $conf['legacy_mode'],
      '#weeks_filter' => (bool) $conf['weeks_filter'],
      '#start_month_filter' => (bool) $conf['start_month_filter'],
      '#duration_filter' => (bool) $conf['duration_filter'],
      '#in_memberships_filter' => (bool) $conf['in_memberships_filter'],
      '#hide_home_branch_block' => (bool) $conf['hide_home_branch_block'],
      '#hide_activities_filter' => (bool) $conf['hide_activities_filter'],
      '#skip_wizard' => (bool) $conf['skip_wizard'],
      '#background_image' => [
        'mobile' => $image_mobile,
        'desktop' => $image_desktop,
      ],
      '#bs_version' => (int) $activity_finder_settings->get('bs_version'),
      '#attached' => [
        'library' => 'openy_activity_finder/activity_finder_4',
        'drupalSettings' => [
          'utm' => $activity_finder_settings->get('allowed_query_arguments'),
        ],
      ],
      '#cache' => [
        'tags' => $this->getCacheTags(),
        'contexts' => $this->getCacheContexts(),
        'max-age' => $this->getCacheMaxAge(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), [OpenyActivityFinderSolrBackend::ACTIVITY_FINDER_CACHE_TAG]);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    [$activity_finder_settings, $backend_service_id, $backend] = $this->getBackend();
    $conf = $this->getConfiguration();

    // Store Daxko limit fields separately since they're strings and not references.
    if ($backend_service_id == 'openy_daxko2.openy_activity_finder_backend') {
      $form['limit_by_category_daxko'] = [
        '#type' => 'textfield',
        '#description' => $this->t('Separate multiple values by a comma and a space, like "ABC123, DEF234".'),
        '#title' => $this->t('Limit by category (Daxko)'),
        '#default_value' => $conf['limit_by_category_daxko'],
      ];
    }
    else {
      $base_by_category = [
        '#type' => 'entity_autocomplete',
        '#description' => $this->t('Separate multiple values by comma.'),
        '#target_type' => 'node',
        '#tags' => TRUE,
        '#selection_settings' => [
          'target_bundles' => ['program_subcategory'],
        ],
        '#size' => 100,
        '#maxlength' => 2048,
      ];

      // Use the allowed location types.
      $location_types = array_keys(array_filter($activity_finder_settings->get('location_types'))) ??
        ['branch', 'camp', 'facility'];
      $base_by_location = [
        '#type' => 'entity_autocomplete',
        '#description' => $this->t(
          'Separate multiple values by comma. Search for title from %types types.',
          ['%types' => join(', ', $location_types)]
        ),
        '#target_type' => 'node',
        '#tags' => TRUE,
        '#selection_settings' => [
          'target_bundles' => $location_types,
        ],
        '#size' => 100,
        '#maxlength' => 2048,
      ];

      $form['location_category'] = [
        '#type' => 'details',
        '#title' => $this->t('Location & Category filters'),
        '#description' => $this->t(
          "Restrict this block to show sessions from only certain Locations or
          Categories. 'Limit' will show <em>only</em> the specified options.
          'Exclude' will <em>remove</em> the specified options. Generally you
          should choose <em>either</em> Exclude <em>or</em> Limit, not both."
        ),
        // Open if any of the containing fields are filled.
        '#open' => ( $conf['limit_by_location'] ||
          $conf['exclude_by_location'] ||
          $conf['limit_by_category'] ||
          $conf['exclude_by_category']
        ),
      ];

      $form['location_category']['limit_by_location'] = $base_by_location + [
        '#title' => $this->t('Limit by location'),
        '#default_value' => $conf['limit_by_location']
          ? $this->entityTypeManager->getStorage('node')->loadMultiple($conf['limit_by_location'])
          : NULL,
      ];
      $form['location_category']['exclude_by_location'] = $base_by_location + [
        '#title' => $this->t('Exclude by location'),
        '#default_value' => $conf['exclude_by_location']
          ? $this->entityTypeManager->getStorage('node')->loadMultiple($conf['exclude_by_location'])
          : NULL,
      ];
      $form['location_category']['limit_by_category'] = $base_by_category + [
        '#title' => $this->t('Limit by category'),
        '#default_value' => $conf['limit_by_category']
          ? $this->entityTypeManager->getStorage('node')->loadMultiple($conf['limit_by_category'])
          : NULL,
      ];

      $form['location_category']['exclude_by_category'] = $base_by_category + [
        '#title' => $this->t('Exclude by category'),
        '#default_value' => $conf['exclude_by_category']
          ? $this->entityTypeManager->getStorage('node')->loadMultiple($conf['exclude_by_category'])
          : NULL,
      ];
    }

    $form['legacy_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Legacy mode'),
      '#description' => $this->t('Enable legacy mode for Activity Finder to emulate v3. Legacy mode disables bookmarks on the results screen, hides the age indicator on results, and removes the time options on the "Days & Times" wizard step.'),
      '#default_value' => $conf['legacy_mode'],
    ];

    $form['weeks_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Weeks filter'),
      '#description' => $this->t('Replace date/time filter with weeks filter. Note: This filter will only return sessions that include "Camp" in the title or room fields.'),
      '#default_value' => $conf['weeks_filter'],
    ];

    $form['additional'] = [
      '#type' => 'details',
      '#title' => $this->t('Additional filters'),
      '#open' => TRUE,
    ];

    $form['additional']['start_month_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Start month'),
      '#description' => $this->t('Allow users to filter by the start month in the Session Time field. This option has no additional configuration.'),
      '#default_value' => $conf['start_month_filter'],
    ];

    $form['additional']['in_memberships_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('In Membership'),
      '#description' => $this->t('Allow users to filter by sessions that are included in their membership. This filters on the ‘In membership’ field on Sessions.'),
      '#default_value' => $conf['in_memberships_filter'],
    ];

    $form['additional']['duration_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Duration'),
      '#description' => $this->t('Allow users to search by the length of the session. Durations are configurable in the @link.', [
        '@link' => Link::createFromRoute(
          'Activity Finder Settings',
          'openy_activity_finder.settings',
          [],
          [
            'attributes' => [
              'target' => '_blank',
            ],
          ],
        )
          ->toString(),
      ]),
      '#default_value' => $conf['duration_filter'],
    ];

    $form['hide_home_branch_block'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide Home Branch info block'),
      '#description' => $this->t('Disables functionality related to the user’s selected home branch.'),
      '#default_value' => $conf['hide_home_branch_block'],
    ];

    $form['hide_activities_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide Activities Filter'),
      '#description' => $this->t('Disables the display of the "Activities" filter at the Activity Finder sidebar.'),
      '#default_value' => $conf['hide_activities_filter'],
    ];

    $form['skip_wizard'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip wizard'),
      '#description' => $this->t('Display results on page load and skip the "Start your search..." wizard.'),
      '#default_value' => $conf['skip_wizard'],
    ];

    // Entity Browser element for background image.
    $form['background_image'] = $this->getEntityBrowserForm(
      'images_library',
      $conf['background_image'],
      1,
      'thumbnail_for_preview'
    );
    // Convert the wrapping container to a details element.
    $form['background_image']['#type'] = 'details';
    $form['background_image']['#title'] = $this->t('Background image');
    $form['background_image']['#open'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['limit_by_category_daxko'] = $form_state->getValue('limit_by_category_daxko');
    $location_category = $form_state->getValue('location_category');
    $this->configuration['limit_by_category'] = $location_category['limit_by_category']
      ? array_column($location_category['limit_by_category'], 'target_id')
      : [];
    $this->configuration['exclude_by_category'] = $location_category['exclude_by_category']
      ? array_column($location_category['exclude_by_category'], 'target_id')
      : [];
    $this->configuration['limit_by_location'] = $location_category['limit_by_location']
      ? array_column($location_category['limit_by_location'], 'target_id')
      : [];
    $this->configuration['exclude_by_location'] = $location_category['exclude_by_location']
      ? array_column($location_category['exclude_by_location'], 'target_id')
      : [];
    $this->configuration['legacy_mode'] = $form_state->getValue('legacy_mode');
    $this->configuration['weeks_filter'] = $form_state->getValue('weeks_filter');
    $additional_filters = $form_state->getValue('additional');
    $this->configuration['start_month_filter'] = $additional_filters['start_month_filter'];
    $this->configuration['duration_filter'] = $additional_filters['duration_filter'];
    $this->configuration['in_memberships_filter'] = $additional_filters['in_memberships_filter'];
    $this->configuration['hide_home_branch_block'] = $form_state->getValue('hide_home_branch_block');
    $this->configuration['hide_activities_filter'] = $form_state->getValue('hide_activities_filter');
    $this->configuration['skip_wizard'] = $form_state->getValue('skip_wizard');
    $this->configuration['background_image'] = $this->getEntityBrowserValue($form_state, 'background_image');
  }

  /**
   * @return array
   */
  public function getBackend(): array {
    $activity_finder_settings = $this->configFactory->get('openy_activity_finder.settings');
    $backend_service_id = $activity_finder_settings->get('backend');
    /** @var \Drupal\openy_activity_finder\OpenyActivityFinderBackendInterface $backend */
    $backend = \Drupal::service($backend_service_id);
    return [$activity_finder_settings, $backend_service_id, $backend];
  }

}
