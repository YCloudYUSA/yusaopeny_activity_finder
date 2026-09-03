<template>
  <div class="select-days-component">
    <Step
      :skip-label="t('Any day (Skip)')"
      :filters-selected="filtersSelected"
      @skip="onSkip"
      @next="onNext"
    >
      <template v-slot:title>
        {{ t('What days are you looking to fill?') }}
      </template>
      <template v-slot:default="{ handleSticky }">
        <Fieldset
          label="Day(s)"
          :collapsible="false"
          :counter="filtersCount"
          :handle-sticky="handleSticky"
        >
          <div class="options">
            <div class="row">
              <div
                v-for="day in days"
                :key="day.search_value"
                class="option check col-6 col-xs-6 col-sm-3"
              >
                <input
                  :id="day.search_value"
                  v-model="selectedDays"
                  type="checkbox"
                  :value="day.value"
                  :disabled="isDisabled(day.search_value)"
                  @change="onChange(day)"
                />
                <label :for="day.search_value" role="button">
                  <span>
                    <span class="title">{{ capitalize(day.search_value) }}</span>
                    <span class="results-count">
                      {{ formatPlural(facetCount(day.search_value), '1 result', '@count results') }}
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
  name: 'SelectDays',
  components: {
    Fieldset,
    Step
  },
  props: {
    modelValue: {
      type: Array,
      required: true
    },
    days: {
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
      selectedDays: this.modelValue
    }
  },
  computed: {
    filtersSelected() {
      return this.modelValue.length >= 1
    },
    filtersCount() {
      return this.modelValue.length
    },
    optionsCount() {
      let count = 0
      for (let key in this.days) {
        count += this.facetCount(this.days[key].search_value)
      }
      return count
    }
  },
  watch: {
    modelValue() {
      this.selectedDays = this.modelValue
    }
  },
  methods: {
    onChange(day) {
      this.trackEvent('selectDays', 'Click on day ' + day.search_value, day.value)
      this.$emit('update:modelValue', this.selectedDays)
    },
    onSkip() {
      this.trackEvent('skip', 'Click on selectDays')
      this.$emit('update:modelValue', [])
      this.$emit('nextStep')
    },
    onNext() {
      this.trackEvent('next', 'Click on selectDays')
      this.$emit('nextStep')
    },
    facetCount(value) {
      // toLowerCase is required here as Daxko facets return days with first capital letter.
      let facet = this.facets.find(x => x.filter.toLowerCase() === value)
      return facet && facet.count ? facet.count : 0
    },
    isDisabled(value) {
      return this.facetCount(value) === 0
    }
  }
}
</script>
