<?php

namespace Drupal\openy_activity_finder_solr\Plugin\search_api\processor;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the start month to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "openy_af_start_month",
 *   label = @Translation("Start Month"),
 *   description = @Translation("Translates datetime values of session to an index of start month date"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class StartMonth extends ProcessorPluginBase implements ContainerFactoryPluginInterface {

  const PROPERTY_NAME = 'search_api_af_start_month';

  const BASE_DATE = '1970-01-01T';

  /**
   * Config Factory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a Facet object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The Config Factory.
   */
  public function __construct(array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactory $config_factory
  ) {
    $this->configFactory = $config_factory;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Start Month'),
        'description' => $this->t("Translates datetime values of session to an index of start month date"),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'is_list' => TRUE,
      ];
      $properties[self::PROPERTY_NAME] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $object = $item->getOriginalObject();
    $entity = $object->getValue();

    if (!$entity->hasField('field_session_time')) {
      return;
    }

    $paragraphs = $entity->field_session_time?->referencedEntities() ?? [];
    if (empty($paragraphs)) {
      return;
    }

    $timezone = $this->getSystemTimezone();

    $values = [];
    foreach ($paragraphs as $paragraph) {
      /** @var \Drupal\Core\Field\FieldItemListInterface $range */
      $range = $paragraph->field_session_time_date;
      if ($range->isEmpty()) {
        continue;
      }

      /** @var \Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem $_period */
      $_period = $range->get(0);
      if ($_period->isEmpty()) {
        continue;
      }

      $_from = DrupalDateTime::createFromTimestamp(strtotime($_period->get('value')->getValue() . 'Z'), $timezone);
      $values[] = $_from->format('n');
    }
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, self::PROPERTY_NAME);
    foreach ($fields as $field) {
      foreach ($values as $value) {
        $field->addValue($value);
      }
    }
  }

  /**
   * Get the system timezone from the site config.
   *
   * @return \DateTimeZone
   * @throws \Exception
   */
  private function getSystemTimezone(): \DateTimeZone {
    return
      new \DateTimeZone($this->configFactory->get('system.date')
        ->get('timezone')['default']);
  }

}
