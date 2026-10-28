<template>
  <div class="select-category-tags-component">
    <Step
      :skip-label="t('Any category tag (Skip)')"
      :filters-selected="filtersSelected"
      @skip="onSkip"
      @next="onNext"
    >
      <template v-slot:title>
        {{ t('What category tags are you interested in?') }}
      </template>
      <template v-slot:default="{ handleSticky }">
        <Fieldset
          :label="t('Categories')"
          collapse-id="accordion-category-tags"
          :collapsible="false"
          :handle-sticky="handleSticky"
        >
          <CategoryTagsFilter
            id="step-category-tags"
            v-model="selectedCategoryTags"
            :category-tags="categoryTags"
            :facets="facets"
            :limit-by-category-tag="limitByCategoryTag"
            :exclude-by-category-tag="excludeByCategoryTag"
          />
        </Fieldset>
      </template>
    </Step>
  </div>
</template>

<script>
import Fieldset from '@/components/Fieldset.vue'
import Step from '@/components/steps/Step.vue'
import CategoryTagsFilter from '@/components/filters/CategoryTags.vue'

export default {
  name: 'SelectCategoryTags',
  components: {
    Fieldset,
    Step,
    CategoryTagsFilter
  },
  props: {
    modelValue: {
      type: Array,
      required: true
    },
    categoryTags: {
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
    limitByCategoryTag: {
      type: Array,
      default: () => []
    },
    excludeByCategoryTag: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      selectedCategoryTags: this.modelValue
    }
  },
  computed: {
    filtersSelected() {
      return this.modelValue.length >= 1
    }
  },
  watch: {
    modelValue() {
      this.selectedCategoryTags = this.modelValue
    },
    selectedCategoryTags() {
      this.$emit('update:modelValue', this.selectedCategoryTags)
    }
  },
  methods: {
    onSkip() {
      this.trackEvent('skip', 'Click on selectCategoryTags')
      this.$emit('update:modelValue', [])
      this.$emit('nextStep')
    },
    onNext() {
      this.trackEvent('next', 'Click on selectCategoryTags')
      this.$emit('nextStep')
    }
  }
}
</script>
