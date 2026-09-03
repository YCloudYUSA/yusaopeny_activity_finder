<template>
  <Foldable
    :label="t('Week(s)')"
    :collapse-id="id + '-toggle'"
    :counter="filtersCount"
    class="weeks-filter-component"
  >
    <div v-for="week in weeks" :key="id + '-week-' + week.value" class="option">
      <input
        :id="id + '-week-' + week.value"
        v-model="selectedWeeks"
        type="checkbox"
        :value="week.value"
      />
      <label :for="id + '-week-' + week.value">{{ week.label }}</label>
    </div>
  </Foldable>
</template>

<script>
import Foldable from '@/components/Foldable'

export default {
  name: 'WeeksFilter',
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
    weeks: {
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
      selectedWeeks: this.modelValue
    }
  },
  computed: {
    filtersCount() {
      return this.selectedWeeks.length
    }
  },
  watch: {
    modelValue() {
      this.selectedWeeks = this.modelValue
    },
    selectedWeeks() {
      this.$emit('update:modelValue', this.selectedWeeks)
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
