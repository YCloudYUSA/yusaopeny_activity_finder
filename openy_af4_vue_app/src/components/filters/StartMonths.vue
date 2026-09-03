<template>
  <Foldable
    :label="t('Start Month(s)')"
    :collapse-id="id + '-toggle'"
    :counter="filtersCount"
    class="start-month-filter-component"
  >
    <div
      v-for="startMonth in startMonths"
      :key="id + '-start-month-' + startMonth.value"
      class="option"
    >
      <input
        :id="id + '-start-month-' + startMonth.value"
        v-model="selectedStartMonths"
        type="checkbox"
        :value="startMonth.value"
      />
      <label :for="id + '-start-month-' + startMonth.value">{{ startMonth.label }}</label>
    </div>
  </Foldable>
</template>

<script>
import Foldable from '@/components/Foldable.vue'

export default {
  name: 'StartMonthsFilter',
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
    startMonths: {
      type: Array,
      required: true
    },
    facets: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      selectedStartMonths: this.modelValue
    }
  },
  computed: {
    filtersCount() {
      return this.selectedStartMonths.length
    }
  },
  watch: {
    modelValue() {
      this.selectedStartMonths = this.modelValue
    },
    selectedStartMonths() {
      this.$emit('update:modelValue', this.selectedStartMonths)
    }
  },
  methods: {
    facetCount(value) {
      let facet = this.facets.find(x => x.filter === value)
      return facet ? facet.count : 0
    }
  }
}
</script>
