<template>
  <div class="select-locations-component">
    <Step
      :skip-label="t('All locations (Skip)')"
      :filters-selected="filtersSelected"
      @skip="onSkip"
      @next="onNext"
    >
      <template v-slot:title>
        {{ t('Do you have any location preferences?') }}
      </template>
      <template v-slot:default="{ handleSticky }">
        <Fieldset
          v-for="(location_type, index) in filteredLocations"
          :key="index"
          :label="location_type.label"
          :collapse-id="'accordion-' + index"
          :counter="subFiltersCount(index)"
          :counter-options="optionsCount(index)"
          accordion="accordion-locations"
          :collapsible="Object.keys(filteredLocations).length !== 1"
          :handle-sticky="handleSticky"
        >
          <div class="options">
            <div class="row">
              <div
                v-for="location in location_type.value"
                :key="location.value"
                class="option check col-12 col-xs-12 col-sm-4"
              >
                <input
                  :id="location.value"
                  v-model="selectedLocations"
                  type="checkbox"
                  :value="location.value"
                  :disabled="isDisabled(location.value)"
                  @change="onChange(location)"
                />
                <label :for="location.value" role="button">
                  <span>
                    <span class="title">{{ location.label }}</span>
                    <span class="results-count">
                      {{ formatPlural(facetCount(location.value), '1 result', '@count results') }}
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
  name: 'SelectLocations',
  components: {
    Fieldset,
    Step
  },
  props: {
    modelValue: {
      type: Array,
      required: true
    },
    locations: {
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
    },
    homeBranchId: {
      type: String,
      default: null
    },
    limitByLocation: {
      type: Array,
      required: true
    },
    excludeByLocation: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      selectedLocations: this.modelValue
    }
  },
  computed: {
    filteredLocations() {
      if (!this.firstStep && !this.excludeByLocation.length && !this.limitByLocation.length) {
        return this.locations
      }

      const filteredLocations = {}
      this.locations.forEach((locationGroup, key) => {

        // Filter out location groups with empty facets if it is the first step.
        if (this.firstStep && !this.optionsCount(key)) {
          return
        }

        const filteredValue = locationGroup.value.filter(item => {
          let r = true

          // Items must pass both tests, so we intentionally do not ELSE these.
          // If there are excludes, the item must NOT be excluded.
          if (this.excludeByLocation.length) {
            r = !this.excludeByLocation.includes(item.value.toString())
          }
          // If there are limits, ONLY items in the limit list are included.
          if (this.limitByLocation.length) {
            r = this.limitByLocation.includes(item.value.toString())
          }

          return r
        }, this)
        // If there are no filtered values then the locationGroup is empty,
        // and we should not add it to the filteredLocations array.
        if (!filteredValue.length) {
          return
        }

        filteredLocations[key] = {
          ...locationGroup,
          value: filteredValue
        }
      }, this)
      return filteredLocations
    },
    filtersSelected() {
      return this.modelValue.length >= 1
    }
  },
  watch: {
    modelValue() {
      this.selectedLocations = this.modelValue
    }
  },
  mounted() {
    if (!this.homeBranchId) {
      return
    }
    if (this.selectedLocations.length) {
      return
    }
    if (!this.facetCount(this.homeBranchId)) {
      return
    }
    this.selectedLocations.push(this.homeBranchId)
  },
  methods: {
    onChange(location) {
      this.trackEvent('selectLocations', 'Click on location ' + location.label, location.value)
      this.$emit('update:modelValue', this.selectedLocations)
    },
    onSkip() {
      this.trackEvent('skip', 'Click on selectLocations')
      this.$emit('update:modelValue', [])
      this.$emit('nextStep')
    },
    onNext() {
      this.trackEvent('next', 'Click on selectLocations')
      this.$emit('nextStep')
    },
    facetCount(value) {
      let facet = this.facets.find(x => x.id === value)
      return facet && facet.count ? facet.count : 0
    },
    isDisabled(value) {
      return this.facetCount(value) === 0
    },
    subFiltersCount(index) {
      let result = 0
      this.modelValue.forEach(item => {
        if (this.locations[index].value.find(location => location.value === item)) {
          result++
        }
      })
      return result
    },
    optionsCount(index) {
      let count = 0
      for (let key in this.locations[index].value) {
        count += this.facetCount(this.locations[index].value[key].value)
      }
      return count
    }
  }
}
</script>
