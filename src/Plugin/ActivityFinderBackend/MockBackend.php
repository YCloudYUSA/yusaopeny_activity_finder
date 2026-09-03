<?php

namespace Drupal\openy_activity_finder\Plugin\ActivityFinderBackend;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\openy_activity_finder\Attribute\ActivityFinderBackend;
use Drupal\openy_activity_finder\Plugin\ActivityFinderBackendPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mock backend plugin.
 *
 * Serves Activity Finder from static fixtures captured from a real Solr
 * response, with zero Solr/DB (W0b DECISIONS D4). Lets AF4 run and the Vue 3
 * migration proceed without standing up Solr. Fixtures validate against
 * fixtures/schema/runProgramSearch.schema.json (D10).
 */
#[ActivityFinderBackend(
  id: 'mock',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Mock (fixtures, no Solr)'),
)]
class MockBackend extends ActivityFinderBackendPluginBase {

  use StringTranslationTrait;

  /**
   * Absolute path to the fixtures directory.
   *
   * @var string
   */
  protected string $fixturesDir;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    /** @var \Drupal\Core\Extension\ModuleExtensionList $module_list */
    $module_list = $container->get('extension.list.module');
    $instance->fixturesDir = $module_list->getPath('openy_activity_finder') . '/fixtures';
    return $instance;
  }

  /**
   * Loads and decodes a fixture file.
   *
   * @param string $name
   *   Fixture file name without extension.
   * @param mixed $default
   *   Value returned when the fixture is missing.
   *
   * @return mixed
   *   The decoded fixture, or $default.
   */
  protected function fixture(string $name, $default = []) {
    $file = $this->fixturesDir . '/' . $name . '.json';
    if (!is_readable($file)) {
      return $default;
    }
    $data = json_decode(file_get_contents($file), TRUE);
    return $data ?? $default;
  }

  /**
   * Loads the captured full search response fixture, shifted to the present.
   */
  protected function searchFixture(): array {
    $data = $this->fixture('runProgramSearch_empty', [
      'count' => 0,
      'facets' => [],
      'table' => [],
      'groupedLocations' => [],
    ]);
    return $this->shiftToNow($data);
  }

  /**
   * Whole-week offset from the fixture's capture date to today.
   *
   * Keeps Mock results in the current timeframe so captured dates never drift
   * into the past. Shifting by whole weeks preserves each session's weekday, so
   * the dates stay consistent with the (untouched) weekly schedule.
   *
   * @param int $captured_year
   *   Set to the fixture's capture year (the rendered "M d" dates carry none).
   *
   * @return int
   *   Number of days to add (a multiple of 7), or 0 when no shift is needed.
   */
  protected function shiftDays(int &$captured_year): int {
    $meta = $this->fixture('_meta', ['captured_year' => 2026, 'captured_month' => 6, 'captured_day' => 8]);
    $captured_year = (int) $meta['captured_year'];
    $anchor = \DateTime::createFromFormat('Y-n-j H:i:s', sprintf('%d-%d-%d 00:00:00', $meta['captured_year'], $meta['captured_month'], $meta['captured_day']));
    $today = new \DateTime('today');
    if (!$anchor || $anchor >= $today) {
      return 0;
    }
    $weeks = (int) ceil(((int) $anchor->diff($today)->days) / 7);
    return $weeks * 7;
  }

  /**
   * Shifts the date-bearing fields of a search response forward to the present.
   */
  protected function shiftToNow(array $data): array {
    $captured_year = 2026;
    $days = $this->shiftDays($captured_year);
    if ($days === 0) {
      return $data;
    }
    $months = [];
    foreach (($data['table'] ?? []) as $i => $row) {
      if (empty($row['dates'])) {
        continue;
      }
      // `dates` may be a single date ("Jun 08") or a range ("Jun 08-Jun 09");
      // shift each part and rejoin. Count the start (first) month for the facet.
      $parts = explode('-', $row['dates']);
      $start_month = NULL;
      foreach ($parts as $p => $part) {
        $date = \DateTime::createFromFormat('M d Y', trim($part) . ' ' . $captured_year);
        if (!$date) {
          continue;
        }
        $date->modify('+' . $days . ' days');
        $parts[$p] = $date->format('M d');
        if ($start_month === NULL) {
          $start_month = (int) $date->format('n');
        }
      }
      $data['table'][$i]['dates'] = implode('-', $parts);
      if ($start_month !== NULL) {
        $months[$start_month] = ($months[$start_month] ?? 0) + 1;
      }
    }
    // Rebuild the start-month facet from the shifted dates.
    if (!empty($data['facets']['af_start_month']) && $months) {
      ksort($months);
      $data['facets']['af_start_month'] = [];
      foreach ($months as $month => $count) {
        $data['facets']['af_start_month'][] = ['filter' => (string) $month, 'count' => $count];
      }
    }
    return $data;
  }

  /**
   * Filters the fixture rows by the search parameters.
   *
   * Best-effort, in-memory matching over the fields the captured rows carry
   * (location, activity category, keyword, day). Not full Solr parity — enough
   * to exercise the wizard without Solr.
   *
   * @param array $parameters
   *   Search parameters.
   *
   * @return array
   *   Matching rows.
   */
  protected function filterRows(array $parameters): array {
    $rows = $this->searchFixture()['table'] ?? [];
    $ids = $parameters['ids'] ?? '';
    $rows = $this->filterByField($rows, 'nid', is_array($ids) ? implode(',', $ids) : $ids);
    $rows = $this->filterByField($rows, 'location_id', $parameters['locations'] ?? '');
    $rows = $this->filterByField($rows, 'program_id', $parameters['categories'] ?? '');
    $rows = $this->filterByAge($rows, $parameters['ages'] ?? '');
    $rows = $this->filterByKeyword($rows, $parameters['keywords'] ?? '');
    $rows = $this->filterByDays($rows, $parameters['days'] ?? '');
    $rows = $this->filterByDaysTimes($rows, $parameters['daystimes'] ?? '');
    // Block-level restrictions: limit (only these) and exclude (remove these).
    $rows = $this->filterByField($rows, 'program_id', $parameters['limit'] ?? '');
    $rows = $this->filterByField($rows, 'location_id', $parameters['limitloc'] ?? '');
    $rows = $this->excludeByField($rows, 'program_id', $parameters['exclude'] ?? '');
    $rows = $this->excludeByField($rows, 'location_id', $parameters['excludeloc'] ?? '');
    return array_values($rows);
  }

  /**
   * Keeps rows whose field value is in the comma-separated selection.
   */
  protected function filterByField(array $rows, string $field, string $csv): array {
    $selected = array_filter(explode(',', $csv));
    if (!$selected) {
      return $rows;
    }
    return array_filter($rows, fn($row) => in_array((string) ($row[$field] ?? ''), $selected, TRUE));
  }

  /**
   * Keeps rows whose min_age/max_age range includes any selected age (months).
   *
   * Ages param is a comma-separated list of age values in months (e.g. "6,12").
   * A row matches if at least one selected age satisfies:
   *   selected >= min_age AND (max_age is null OR selected <= max_age)
   */
  protected function filterByAge(array $rows, string $csv): array {
    $selected = array_filter(array_map('intval', explode(',', $csv)));
    if (!$selected) {
      return $rows;
    }
    return array_filter($rows, function ($row) use ($selected) {
      $min = isset($row['min_age']) && $row['min_age'] !== null ? (int) $row['min_age'] : 0;
      $max = isset($row['max_age']) && $row['max_age'] !== null ? (int) $row['max_age'] : PHP_INT_MAX;
      foreach ($selected as $age) {
        if ($age >= $min && $age <= $max) {
          return TRUE;
        }
      }
      return FALSE;
    });
  }

  /**
   * Recomputes af_weekdays_parts_of_day facets from filtered rows.
   *
   * Each session contributes two counts per weekday it runs on: the Anytime
   * slot (always) and the specific time-of-day slot (Morning/Afternoon/Evening)
   * derived from the row's start time. This mirrors how Solr tags sessions.
   */
  protected function recomputeWeekdaysPartsOfDay(array $filtered): array {
    $daysTimes = $this->fixture('getDaysTimes');
    $dayMap = [];
    foreach ($daysTimes as $day) {
      $sv = strtolower((string) ($day['search_value'] ?? ''));
      $dayMap[$sv] = array_column($day['value'] ?? [], 'value');
    }

    $counts = [];
    foreach ($filtered as $row) {
      $days     = array_filter(array_map('trim', explode(',', strtolower((string) ($row['days'] ?? '')))));
      $timesStr = (string) ($row['times'] ?? '');
      $start    = $this->parseStartHour($timesStr);
      $end      = $this->parseEndHour($timesStr) ?? $start;
      // Determine which time-of-day slots this session overlaps (Solr overlap logic).
      // Morning [0,12): session overlaps if start < 12.
      // Afternoon [12,17): session overlaps if start < 17 AND end >= 12.
      // Evening [17,∞): session overlaps if start >= 17 OR end > 17.
      $sessionSlots = [];
      if ($start !== NULL && $end !== NULL) {
        if ($start < 12) { $sessionSlots[] = 1; }
        if ($start < 17 && $end >= 12) { $sessionSlots[] = 2; }
        if ($start >= 17 || $end > 17) { $sessionSlots[] = 3; }
      }
      foreach ($days as $dayName) {
        if (!isset($dayMap[$dayName])) {
          continue;
        }
        $slots = $dayMap[$dayName];
        // Index 0 = Anytime: every session on this day.
        if (isset($slots[0])) {
          $counts[$slots[0]] = ($counts[$slots[0]] ?? 0) + 1;
        }
        // Count each time-of-day slot the session touches.
        foreach ($sessionSlots as $slotIndex) {
          if (isset($slots[$slotIndex])) {
            $counts[$slots[$slotIndex]] = ($counts[$slots[$slotIndex]] ?? 0) + 1;
          }
        }
      }
    }

    $result = [];
    foreach ($daysTimes as $day) {
      foreach ($day['value'] ?? [] as $slot) {
        $filter = (string) $slot['value'];
        if (isset($counts[$filter])) {
          $result[] = ['filter' => $filter, 'count' => $counts[$filter]];
        }
      }
    }
    return $result;
  }

  /**
   * Parses the start hour (0-23) from a times string like "5:00pm-6:00pm".
   */
  protected function parseStartHour(string $times): ?int {
    if (!preg_match('/^(\d+):\d+\s*(am|pm)/i', trim($times), $m)) {
      return NULL;
    }
    $hour = (int) $m[1];
    $ampm = strtolower($m[2]);
    if ($ampm === 'pm' && $hour !== 12) {
      $hour += 12;
    }
    elseif ($ampm === 'am' && $hour === 12) {
      $hour = 0;
    }
    return $hour;
  }

  /**
   * Parses the end hour (0-23) from a times string like "5:00pm-6:00pm".
   */
  protected function parseEndHour(string $times): ?int {
    if (!preg_match('/[-–]\s*(\d+):\d+\s*(am|pm)\s*$/i', trim($times), $m)) {
      return NULL;
    }
    $hour = (int) $m[1];
    $ampm = strtolower($m[2]);
    if ($ampm === 'pm' && $hour !== 12) {
      $hour += 12;
    }
    elseif ($ampm === 'am' && $hour === 12) {
      $hour = 0;
    }
    return $hour;
  }

  /**
   * Maps a start hour to a time-of-day slot index matching getDaysTimes.
   *
   * 1 = Morning  (Open – 12 p.m.)
   * 2 = Afternoon (12 – 5 p.m.)
   * 3 = Evening  (5 p.m. – Close)
   */
  protected function timeToSlotIndex(?int $hour): ?int {
    if ($hour === NULL) {
      return NULL;
    }
    if ($hour < 12) {
      return 1;
    }
    if ($hour < 17) {
      return 2;
    }
    return 3;
  }

  /**
   * Removes rows whose field value is in the comma-separated selection.
   */
  protected function excludeByField(array $rows, string $field, string $csv): array {
    $excluded = array_filter(explode(',', $csv));
    if (!$excluded) {
      return $rows;
    }
    return array_filter($rows, fn($row) => !in_array((string) ($row[$field] ?? ''), $excluded, TRUE));
  }

  /**
   * Keeps rows whose name or description contains the keyword.
   */
  protected function filterByKeyword(array $rows, string $keyword): array {
    $keyword = trim($keyword);
    if ($keyword === '') {
      return $rows;
    }
    return array_filter($rows, fn($row) => stripos(($row['name'] ?? '') . ' ' . ($row['description'] ?? ''), $keyword) !== FALSE);
  }

  /**
   * Keeps rows scheduled on any of the comma-separated day names.
   */
  protected function filterByDays(array $rows, string $csv): array {
    $selected = array_filter(array_map('strtolower', explode(',', $csv)));
    if (!$selected) {
      return $rows;
    }
    return array_filter($rows, function ($row) use ($selected) {
      $row_days = strtolower($row['days'] ?? '');
      foreach ($selected as $day) {
        if ($day !== '' && str_contains($row_days, $day)) {
          return TRUE;
        }
      }
      return FALSE;
    });
  }

  /**
   * Filters rows to those matching any of the selected day+time slot IDs.
   *
   * Slot IDs come from getDaysTimes fixture (e.g. 13 = Monday Evening).
   * Slot index within day: 0=Anytime, 1=Morning(<12), 2=Afternoon(12-17), 3=Evening(>=17).
   * Uses the same overlap logic as recomputeWeekdaysPartsOfDay().
   */
  protected function filterByDaysTimes(array $rows, string $csv): array {
    $selected = array_filter(array_map('trim', explode(',', $csv)));
    if (!$selected) {
      return $rows;
    }
    // Build map: slot_value => [day_name, slot_index].
    $slotMap = [];
    foreach ($this->fixture('getDaysTimes') as $day) {
      $dayName = strtolower((string) ($day['search_value'] ?? ''));
      foreach ($day['value'] ?? [] as $idx => $slot) {
        $slotMap[(string) $slot['value']] = [$dayName, $idx];
      }
    }
    return array_filter($rows, function ($row) use ($selected, $slotMap) {
      $rowDays = array_filter(array_map('trim', explode(',', strtolower((string) ($row['days'] ?? '')))));
      $timesStr = (string) ($row['times'] ?? '');
      $start = $this->parseStartHour($timesStr);
      $end   = $this->parseEndHour($timesStr) ?? $start;
      foreach ($selected as $slotId) {
        if (!isset($slotMap[$slotId])) {
          continue;
        }
        [$dayName, $slotIdx] = $slotMap[$slotId];
        if (!in_array($dayName, $rowDays, TRUE)) {
          continue;
        }
        if ($slotIdx === 0) {
          return TRUE;
        }
        if ($start === NULL) {
          continue;
        }
        if ($slotIdx === 1 && $start < 12) {
          return TRUE;
        }
        if ($slotIdx === 2 && $start < 17 && $end >= 12) {
          return TRUE;
        }
        if ($slotIdx === 3 && ($start >= 17 || $end > 17)) {
          return TRUE;
        }
      }
      return FALSE;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getResultsCount(array $parameters): int {
    return count($this->filterRows($parameters));
  }

  /**
   * {@inheritdoc}
   */
  public function getFacets(array $parameters): array {
    $facets = $this->searchFixture()['facets'] ?? [];
    $filtered = $this->filterRows($parameters);

    // Recompute sub-category counts (field_activity_category).
    $subCounts = [];
    foreach ($filtered as $row) {
      $pid = (string) ($row['program_id'] ?? '');
      if ($pid !== '') {
        $subCounts[$pid] = ($subCounts[$pid] ?? 0) + 1;
      }
    }
    if (isset($facets['field_activity_category'])) {
      $facets['field_activity_category'] = array_values(array_map(
        function ($cat) use ($subCounts) {
          $cat['count'] = $subCounts[$cat['filter']] ?? 0;
          return $cat;
        },
        $facets['field_activity_category']
      ));
    }

    // Recompute top-level group counts (field_category_program).
    if (isset($facets['field_category_program'])) {
      $groupMap = [];
      foreach ($this->fixture('getCategories') as $group) {
        foreach ($group['value'] as $sub) {
          $groupMap[(string) $sub['value']] = $group['label'];
        }
      }
      $groupCounts = [];
      foreach ($filtered as $row) {
        $group = $groupMap[(string) ($row['program_id'] ?? '')] ?? null;
        if ($group) {
          $groupCounts[$group] = ($groupCounts[$group] ?? 0) + 1;
        }
      }
      $facets['field_category_program'] = array_values(array_map(
        function ($cat) use ($groupCounts) {
          $cat['count'] = $groupCounts[$cat['filter']] ?? 0;
          return $cat;
        },
        $facets['field_category_program']
      ));
    }

    // Recompute location counts.
    if (isset($facets['locations'])) {
      $locCounts = [];
      foreach ($filtered as $row) {
        $lid = (string) ($row['location_id'] ?? '');
        if ($lid !== '') {
          $locCounts[$lid] = ($locCounts[$lid] ?? 0) + 1;
        }
      }
      $facets['locations'] = array_values(array_map(
        function ($loc) use ($locCounts) {
          $loc['count'] = $locCounts[(string) $loc['id']] ?? 0;
          return $loc;
        },
        $facets['locations']
      ));
    }

    // Recompute weekday+time-of-day slot counts (af_weekdays_parts_of_day).
    $facets['af_weekdays_parts_of_day'] = $this->recomputeWeekdaysPartsOfDay($filtered);

    return $facets;
  }

  /**
   * {@inheritdoc}
   */
  public function getResults(array $parameters, int $offset, int $limit, int $log_id): array {
    return array_slice($this->filterRows($parameters), $offset, $limit);
  }

  /**
   * {@inheritdoc}
   */
  public function getLocations() {
    return $this->fixture('getLocations');
  }

  /**
   * {@inheritdoc}
   */
  public function getSortOptions() {
    return $this->fixture('getSortOptions');
  }

  /**
   * {@inheritdoc}
   */
  public function getRelevanceSort() {
    return $this->fixture('getRelevanceSort', '');
  }

  /**
   * {@inheritdoc}
   */
  public function getAges() {
    return $this->fixture('getAges');
  }

  /**
   * {@inheritdoc}
   */
  public function getDaysOfWeek() {
    return $this->fixture('getDaysOfWeek');
  }

  /**
   * {@inheritdoc}
   */
  public function getPartsOfDay() {
    return $this->fixture('getPartsOfDay');
  }

  /**
   * {@inheritdoc}
   */
  public function getDaysTimes() {
    return $this->fixture('getDaysTimes');
  }

  /**
   * {@inheritdoc}
   */
  public function getStartMonths() {
    return $this->fixture('getStartMonths');
  }

  /**
   * {@inheritdoc}
   */
  public function getDurations() {
    return $this->fixture('getDurations');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeeks() {
    return $this->fixture('getWeeks');
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    return $this->fixture('getCategories');
  }

  /**
   * {@inheritdoc}
   */
  public function getCategoriesTopLevel() {
    return $this->fixture('getCategoriesTopLevel');
  }

  /**
   * {@inheritdoc}
   */
  public function getCategoriesType() {
    return $this->fixture('getCategoriesType', 'multiple');
  }

  /**
   * {@inheritdoc}
   */
  public function getFiltersSectionConfig() {
    return $this->fixture('getFiltersSectionConfig', []);
  }

  /**
   * {@inheritdoc}
   */
  public function getProgramsMoreInfo($request) {
    return [];
  }

}
