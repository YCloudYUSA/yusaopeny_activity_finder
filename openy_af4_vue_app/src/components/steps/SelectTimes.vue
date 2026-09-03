<template>
  <div class="select-times-component">
    <Step
      :skip-label="t('Any time (Skip)')"
      :filters-selected="filtersSelected"
      @skip="onSkip"
      @next="onNext"
    >
      <template v-slot:title>
        {{ t('What times are you looking to fill?') }}
      </template>
      <template v-slot:default="{ handleSticky }">
        <Fieldset
          :label="t('Time(s)')"
          :collapsible="false"
          :counter="filtersCount"
          :handle-sticky="handleSticky"
        >
          <div class="options">
            <div class="row">
              <div
                v-for="time in times"
                :key="time.value"
                class="option check col-12 col-xs-12 col-sm-4"
              >
                <input
                  :id="time.value"
                  v-model="selectedTimes"
                  type="checkbox"
                  :value="time.value"
                  :disabled="isDisabled(time.value)"
                  @change="onChange(time)"
                />
                <label :for="time.value" role="button">
                  <span>
                    <span class="title">{{ time.label }}</span>
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
  name: 'SelectTimes',
  components: {
    Fieldset,
    Step
  },
  props: {
    modelValue: {
      type: Array,
      required: true
    },
    times: {
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
      selectedTimes: this.modelValue
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
      for (let key in this.times) {
        count += this.facetCount(this.times[key].value)
      }
      return count
    }
  },
  watch: {
    modelValue() {
      this.selectedTimes = this.modelValue
    }
  },
  methods: {
    onChange(time) {
      this.trackEvent('selectTimes', 'Click on time ' + time.label, time.value)
      this.$emit('update:modelValue', this.selectedTimes)
    },
    onSkip() {
      this.trackEvent('skip', 'Click on selectTimes')
      this.$emit('update:modelValue', [])
      this.$emit('nextStep')
    },
    onNext() {
      this.trackEvent('next', 'Click on selectTimes')
      this.$emit('nextStep')
    },
    facetCount(value) {
      let facet = this.facets.find(x => x.filter === value)
      return facet && facet.count ? facet.count : 0
    },
    isDisabled(value) {
      return this.facetCount(value) === 0
    }
  }
}
</script>
