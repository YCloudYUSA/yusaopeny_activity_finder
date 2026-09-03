<?php

namespace Drupal\openy_activity_finder_solr\Plugin\search_api\processor;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the Weeks to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "openy_af_week",
 *   label = @Translation("Weeks"),
 *   description = @Translation("Creates weeks e.g. Week 1: June 1."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class Week extends ProcessorPluginBase implements ContainerFactoryPluginInterface {

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
   * Sets the config factory service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @return $this
   */
  protected function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Weeks'),
        'description' => $this->t('Creates weeks e.g. Week 1: June 1.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'is_list' => FALSE,
      ];
      $properties['search_api_af_weeks'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $object = $item->getOriginalObject();
    $entity = $object->getValue();

    $session_room_value = $entity->field_session_room->value ?? '';

    preg_match('/Camp/', $entity->getTitle(), $matches_title);
    preg_match('/Camp/', $session_room_value, $matches_room);
    if ((!empty($matches_title[0]) || !empty($matches_room[0])) && $entity->field_session_time) {
      $dates = $entity->field_session_time->referencedEntities();
      foreach ($dates as $date) {
        if (empty($date) || empty($date->field_session_time_date->getValue())) {
          continue;
        }
        $_period = $date->field_session_time_date->getValue()[0];
        $week_start_date = DrupalDateTime::createFromTimestamp(strtotime($_period['value'] . 'Z'))->format('n-j-Y');
        // Check if date is in the list of camp weeks listed in config.
        $weeks = $this->configFactory
          ->get('openy_activity_finder.settings')
          ->get('weeks');
        preg_match('/' . $week_start_date . '/', $weeks, $matched_weeks);
      }

      if (!empty($week_start_date) && !empty($matched_weeks[0])) {
        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($item->getFields(), NULL, 'search_api_af_weeks');
        foreach ($fields as $field) {
          $field->addValue($week_start_date);
        }
      }
    }
  }

}
