<template>
  <div class="locations-filter-component">
    <Foldable
      v-for="(item_type, index) in filteredLocations"
      :key="id + '-location-type-' + index"
      :label="item_type.label"
      :collapse-id="id + '-toggle-' + index"
      :counter="subFiltersCount(index)"
    >
      <div v-for="item in item_type.value" :key="id + '-location-' + item.value" class="option">
        <input
          :id="id + '-location-' + item.value"
          v-model="selectedLocations"
          type="checkbox"
          :value="item.value"
        />
        <label :for="id + '-location-' + item.value">
          {{ item.label }}
        </label>
      </div>
    </Foldable>
  </div>
</template>

<script>
import Foldable from '@/components/Foldable.vue'

export default {
  name: 'LocationsFilter',
  components: {
    Foldable
  },
  props: {
    modelValue: {
      type: Array,
      required: true
    },
    id: {
      type: String,
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
      if (!this.excludeByLocation.length && !this.limitByLocation.length) {
        return this.locations
      }

      const filteredLocations = {}
      this.locations.forEach((locationGroup, key) => {
        // Filter out excluded locations.
        filteredLocations[key] = locationGroup

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
    }
  },
  watch: {
    modelValue() {
      this.selectedLocations = this.modelValue
    },
    selectedLocations() {
      this.$emit('update:modelValue', this.selectedLocations)
    }
  },
  methods: {
    subFiltersCount(index) {
      let result = 0
      this.selectedLocations.forEach(item => {
        if (this.locations[index].value.find(location => location.value === item)) {
          result++
        }
      })
      return result
    },
    facetCount(value) {
      let facet = this.facets.find(x => x.id === value)
      return facet ? facet.count : 0
    }
  }
}
</script>
