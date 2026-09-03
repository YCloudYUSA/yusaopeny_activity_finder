<template>
  <div class="select-days-times-component">
    <Step
      :skip-label="t('Any day & time (Skip)')"
      :filters-selected="filtersSelected"
      @skip="onSkip"
      @next="onNext"
    >
      <template v-slot:title>
        {{ t('What day or time are you looking for?') }}
      </template>
      <template v-slot:default="{ handleSticky }">
        <Fieldset
          v-for="(day, index) in filteredDaysTimes"
          :key="index"
          :label="day.search_value"
          :counter="subFiltersCount(index)"
          :collapse-id="'accordion-' + index"
          :counter-options="optionsCount(index)"
          accordion="accordion-days-times"
          :collapsible="Object.keys(filteredDaysTimes).length !== 1"
          :handle-sticky="handleSticky"
        >
          <div class="options">
            <div class="row">
              <div
                v-for="time in day.value"
                :key="time.value"
                class="option check col-6 col-xs-6 col-sm-3"
              >
                <input
                  :id="time.value"
                  v-model="selectedDaysTimes"
                  type="checkbox"
                  :value="time.value"
                  :disabled="isDisabled(time.value)"
                  @change="onChange(day, time)"
                />
                <label :for="time.value" role="button">
                  <span>
                    <span class="title">{{ time.label }}</span>
                    <template v-if="time.description">
                      <span class="description">
                        {{ time.description }}
                      </span>
                    </template>
                    <span class="results-count">
                      {{ formatPlural(facetCount(time.value), '1 result', '@count results') }}
                    </span>
                  </span>
                </label>
              </div>
            </div>
          </div>
        </Fieldset>
      </template>
    </Step>
  </div>
</template>

<script>
import Fieldset from '@/components/Fieldset.vue'
import Step from '@/components/steps/Step.vue'

export default {
  name: 'SelectDaysTimes',
  components: {
    Fieldset,
    Step
  },
  props: {
    modelValue: {
      type: Array,
      required: true
    },
    daysTimes: {
      type: Array,
      required: true
    },
    facets: {
      type: Array,
      required: true
    },
    firstStep: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      selectedDaysTimes: this.modelValue
    }
  },
  computed: {
    filteredDaysTimes() {
      if (!this.firstStep) {
        return this.daysTimes
      }

      const filteredDaysTimes = {}
      for (let key in this.daysTimes) {
        if (this.optionsCount(key) > 0) {
          filteredDaysTimes[key] = this.daysTimes[key]
        }
      }
      return filteredDaysTimes
    },
    filtersSelected() {
      return this.modelValue.length >= 1
    },
    firstItemWithOptions() {
      for (let key in this.days) {
        if (this.optionsCount(key) > 0) {
          return key
        }
      }
      return 0
    }
  },
  watch: {
    modelValue() {
      this.selectedDaysTimes = this.modelValue
    }
  },
  methods: {
    onChange(day, time) {
      this.trackEvent(
        'selectDaysTimes',
        'Click on day ' + day.search_value + ' and time ' + time.label,
        time.value
      )
      this.$emit('update:modelValue', this.selectedDaysTimes)
    },
    onSkip() {
      this.trackEvent('skip', 'Click on selectDaysTimes')
      this.$emit('update:modelValue', [])
      this.$emit('nextStep')
    },
    onNext() {
      this.trackEvent('next', 'Click on selectDaysTimes')
      this.$emit('nextStep')
    },
    facetCount(value) {
      if (typeof this.facets === 'undefined') {
        return 0
      }
      let facet = this.facets.find(x => x.filter === value)
      return facet && facet.count ? facet.count : 0
    },
    isDisabled(value) {
      return this.facetCount(value) === 0
    },
    subFiltersCount(index) {
      let result = 0
      this.modelValue.forEach(item => {
        if (this.daysTimes[index].value.find(day => String(day.value) === String(item))) {
          result++
        }
      })
      return result
    },
    optionsCount(index) {
      let count = 0
      for (let key in this.daysTimes[index].value) {
        count += this.facetCount(this.daysTimes[index].value[key].value)
      }
      return count
    }
  }
}
</script>
<style lang="scss">
.select-days-times-component {
  .fieldset-title {
    .title {
      text-transform: capitalize;
    }
  }
}
</style>
