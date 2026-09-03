<template>
  <div class="select-ages-component">
    <Step
      :skip-label="t('Any age (skip)')"
      :filters-selected="filtersSelected"
      @skip="onSkip"
      @next="onNext"
    >
      <template v-slot:title>
        {{ t('What ages are you searching for?') }}
        <div v-if="maxAges">{{ t('Choose a maximum of !maxAges ages.', { '!maxAges': maxAges }) }} </div>
      </template>
      <template v-slot:default="{ handleSticky }">
        <Fieldset
          label="Age(s)"
          :collapsible="false"
          :counter="filtersCount"
          :counter-max="maxAges"
          :handle-sticky="handleSticky"
        >
          <div class="options">
            <div class="row">
              <div
                v-for="age in ages"
                :key="age.value"
                class="option check col-6 col-xs-6 col-sm-3"
              >
                <input
                  :id="age.value"
                  v-model="selectedAges"
                  type="checkbox"
                  :value="age.value"
                  :disabled="isDisabled(age.value)"
                  @change="onChange(age)"
                />
                <label
                  :id="'label-' + age.value"
                  :for="age.value"
                  role="button"
                  :title="isTooltipDisplay(age.value) ? t('Please unselect any of the selected options first') : undefined"
                >
                  <span>
                    <span class="title">{{ age.label }}</span>
                    <span v-if="facetCount(age.value) !== null" class="results-count">
                      {{ formatPlural(facetCount(age.value), '1 result', '@count results') }}
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
  name: 'SelectAges',
  components: {
    Fieldset,
    Step
  },
  props: {
    modelValue: {
      type: Array,
      required: true
    },
    ages: {
      type: Array,
      required: true
    },
    maxAges: {
      type: Number,
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
      selectedAges: this.modelValue
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
      for (let key in this.ages) {
        count += this.facetCount(this.ages[key].value)
      }
      return count
    }
  },
  watch: {
    modelValue() {
      this.selectedAges = this.modelValue
    }
  },
  methods: {
    onChange(age) {
      this.trackEvent('selectAges', 'Click on age ' + age.label, age.value)
      this.$emit('update:modelValue', this.selectedAges)
    },
    onSkip() {
      this.trackEvent('skip', 'Click on selectAges')
      this.$emit('update:modelValue', [])
      this.$emit('nextStep')
    },
    onNext() {
      this.trackEvent('next', 'Click on selectAges')
      this.$emit('nextStep')
    },
    facetCount(value) {
      if (this.facets.length === 0) {
        return null
      }
      let facet = this.facets.find(x => x.value === value)
      return facet && facet.count ? facet.count : 0
    },
    isDisabled(value) {
      return !!(
        this.facetCount(value) === 0 ||
        (this.maxAges && this.modelValue.length >= this.maxAges && !this.modelValue.includes(value))
      )
    },
    isTooltipDisplay(value) {
      return !!(
        this.maxAges &&
        this.modelValue.length >= this.maxAges &&
        !this.modelValue.includes(value) &&
        this.facetCount(value) > 0
      )
    }
  }
}
</script>
