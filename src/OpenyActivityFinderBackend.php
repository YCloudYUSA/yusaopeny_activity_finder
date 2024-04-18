<?php

namespace Drupal\openy_activity_finder;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Implements interface.
 */
abstract class OpenyActivityFinderBackend implements OpenyActivityFinderBackendInterface {

  use StringTranslationTrait;

  /**
   * Activity Finder configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Site's default timezone.
   *
   * @var \DateTimeZone
   */
  protected $timezone;

  /**
   * {@inheritdoc}
   */
  abstract public function runProgramSearch($parameters, $log_id);

  /**
   * {@inheritdoc}
   */
  abstract public function getLocations();

  /**
   * OpenyActivityFinderBackend constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('openy_activity_finder.settings');
    $this->timezone = new \DateTimeZone($config_factory->get('system.date')->get('timezone')['default']);
  }

  /**
   * {@inheritdoc}
   */
  public function getAges() {
    $ages = [];

    $ages_config = $this->config->get('ages');

    if (!$ages_config) {
      return [];
    }

    foreach (explode("\n", $ages_config) as $row) {
      $row = trim($row);
      [$months, $label] = explode(',', $row);
      $ages[] = [
        'label' => $label,
        'value' => $months,
      ];
    }

    return $ages;
  }

  /**
   * Get weeks from configuration.
   */
  public function getWeeks() {
    $weeks = [];

    $weeks_config = $this->config->get('weeks');

    if (!$weeks_config) {
      return [];
    }

    foreach (explode("\n", $weeks_config) as $row) {
      $row = trim($row);
      [$months, $label] = explode(',', $row);
      $weeks[] = [
        'label' => $label,
        'value' => $months,
      ];
    }

    return $weeks;
  }

  /**
   * {@inheritdoc}
   */
  public function getDurations() {
    $durations = [];

    $durations_config = $this->config->get('durations');

    if (!$durations_config) {
      return [];
    }

    foreach (explode(PHP_EOL, $durations_config) as $row) {
      $row = trim($row);
      [$duration, $label] = explode('|', $row);
      $durations[] = [
        'label' => $label,
        'value' => $duration,
      ];
    }

    return $durations;
  }

  /**
   * {@inheritdoc}
   */
  public function getDaysOfWeek() {
    return [
      [
        'label' => 'Mon',
        'search_value' => 'monday',
        'value' => '1',
      ],
      [
        'label' => 'Tue',
        'search_value' => 'tuesday',
        'value' => '2',
      ],
      [
        'label' => 'Wed',
        'search_value' => 'wednesday',
        'value' => '3',
      ],
      [
        'label' => 'Thu',
        'search_value' => 'thursday',
        'value' => '4',
      ],
      [
        'label' => 'Fri',
        'search_value' => 'friday',
        'value' => '5',
      ],
      [
        'label' => 'Sat',
        'search_value' => 'saturday',
        'value' => '6',
      ],
      [
        'label' => 'Sun',
        'search_value' => 'sunday',
        'value' => '7',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPartsOfDay() {
    return [
      [
        'label' => $this->t('Morning'),
        'description' => $this->t('Open - 12 p.m.'),
        'value' => '1',
      ],
      [
        'label' => $this->t('Afternoon'),
        'description' => $this->t('12 - 5 p.m.'),
        'value' => '2',
      ],
      [
        'label' => $this->t('Evening'),
        'description' => $this->t('5 p.m. - Close'),
        'value' => '3',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getStartMonths() {
    return [
      [
        'label' => $this->t('January'),
        'value' => '1',
      ],
      [
        'label' => $this->t('February'),
        'value' => '2',
      ],
      [
        'label' => $this->t('March'),
        'value' => '3',
      ],
      [
        'label' => $this->t('April'),
        'value' => '4',
      ],
      [
        'label' => $this->t('May'),
        'value' => '5',
      ],
      [
        'label' => $this->t('June'),
        'value' => '6',
      ],
      [
        'label' => $this->t('July'),
        'value' => '7',
      ],
      [
        'label' => $this->t('August'),
        'value' => '8',
      ],
      [
        'label' => $this->t('September'),
        'value' => '9',
      ],
      [
        'label' => $this->t('October'),
        'value' => '10',
      ],
      [
        'label' => $this->t('November'),
        'value' => '11',
      ],
      [
        'label' => $this->t('December'),
        'value' => '12',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDaysTimes() {
    $weekdays = $this->getDaysOfWeek();
    $parts_of_day = $this->getPartsOfDay();
    array_unshift($parts_of_day, [
      'label' => $this->t('Anytime'),
      'description' => '',
      'value' => '0',
    ]);

    $values = [];
    foreach ($weekdays as $day) {
      $value = $day;
      $value['value'] = [];
      foreach ($parts_of_day as $time) {
        $time['value'] = $day['value'] . $time['value'];
        $value['value'][] = $time;
      }

      $values[] = $value;
    }

    return $values;
  }

  /**
   * Get categories type.
   */
  public function getCategoriesType() {
    return 'multiple';
  }

  /**
   * Get the "Group collapse settings" from the AF settings page.
   */
  public function getFiltersSectionConfig() {
    $config = [];
    foreach (['schedule', 'category', 'locations', 'additional'] as $name) {
      $config[$name] = TRUE;
      $value = $this->config->get("{$name}_collapse_group");
      if ($value) {
        $config[$name] = strstr($value, 'collapsed') || strstr($value, 'disabled');
      }
    }
    return $config;
  }

}
