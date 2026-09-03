<template>
  <Modal id="activity-finder-filters" v-model="visible" title="Filters" narrow>
    <template v-slot:default>
      <div class="filters-modal-content">
        <div class="row">
          <div class="col-12 col-xs-12">
            <div class="content">
              <div class="description">{{ t('The following filters have been applied') }}:</div>
              <ul>
                <li v-for="(item, index) in filterList" :key="index">
                  {{ item }}
                </li>
              </ul>
              <div class="text-center">
                <button type="button" class="btn btn-lg view-results" @click="viewResults()">
                  {{ t('View results') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </Modal>
</template>

<script>
import Modal from '@/components/modals/Modal.vue'
import {
  activitiesList,
  agesList,
  locationsList,
  daysList,
  timesList,
  daysTimesList,
  weeksList,
  durationsList,
  startMonthsList
} from '@/helpers/filters'

export default {
  name: 'FiltersModal',
  components: {
    Modal
  },
  props: {
    modelValue: {
      type: Boolean,
      default: false
    },
    ages: {
      type: Array,
      required: true
    },
    selectedAges: {
      type: Array,
      required: true
    },
    days: {
      type: Array,
      required: true
    },
    selectedDays: {
      type: Array,
      required: true
    },
    times: {
      type: Array,
      required: true
    },
    selectedTimes: {
      type: Array,
      required: true
    },
    daysTimes: {
      type: Array,
      required: true
    },
    selectedDaysTimes: {
      type: Array,
      required: true
    },
    weeks: {
      type: Array,
      required: true
    },
    selectedWeeks: {
      type: Array,
      required: true
    },
    activities: {
      type: Array,
      required: true
    },
    selectedActivities: {
      type: Array,
      required: true
    },
    locations: {
      type: Array,
      required: true
    },
    selectedLocations: {
      type: Array,
      required: true
    },
    durations: {
      type: Array,
      required: true
    },
    selectedDurations: {
      type: Array,
      required: true
    },
    startMonths: {
      type: Array,
      required: true
    },
    selectedStartMonths: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      visible: this.modelValue
    }
  },
  computed: {
    filterList() {
      return [
        ...activitiesList(this.selectedActivities, this.activities),
        ...agesList(this.selectedAges, this.ages),
        ...locationsList(this.selectedLocations, this.locations),
        ...daysList(this.selectedDays, this.days),
        ...timesList(this.selectedTimes, this.times),
        ...daysTimesList(this.selectedDaysTimes, this.daysTimes),
        ...weeksList(this.selectedWeeks, this.weeks),
        ...durationsList(this.selectedDurations, this.durations),
        ...startMonthsList(this.selectedStartMonths, this.startMonths)
      ].sort()
    }
  },
  watch: {
    modelValue() {
      this.visible = this.modelValue
    },
    visible() {
      this.$emit('update:modelValue', this.visible)
    }
  },
  methods: {
    viewResults() {
      this.visible = false
      this.$emit('viewResults')
    }
  }
}
</script>

<style lang="scss">
.filters-modal-content {
  .content {
    color: $af-black;
    padding: 20px 10px;

    .description {
      margin-bottom: 20px;
    }

    ul {
      padding-left: 25px;
      margin-bottom: 20px;

      li {
        margin-bottom: 10px;

        &:last-child {
          margin-bottom: 0;
        }
      }
    }

    .view-results {
      border-radius: 5px;
      width: 100%;
      background-color: $af-violet;
      color: $white;
      font-weight: bold;
      line-height: 50px;
      padding: 0;
    }
  }
}
</style>
