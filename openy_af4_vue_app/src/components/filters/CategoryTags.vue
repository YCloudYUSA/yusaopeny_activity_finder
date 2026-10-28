<template>
  <div class="category-tags-filter-component">
    <!-- Sidebar list style: simple rows like DaysTimes -->
    <template v-if="variant === 'list'">
      <div
        v-for="tag in filteredTags"
        :key="id + '-tag-' + tag.value"
        class="ct-option"
      >
        <input
          :id="id + '-tag-' + tag.value"
          v-model="selectedTags"
          type="checkbox"
          :value="tag.value"
        />
        <label :for="id + '-tag-' + tag.value">{{ tag.label }}</label>
      </div>
    </template>

    <!-- Wizard card grid style: like Ages -->
    <template v-else>
      <div class="options">
        <div class="row">
          <div
            v-for="tag in filteredTags"
            :key="id + '-tag-' + tag.value"
            class="option check col-6 col-xs-6 col-sm-3"
          >
            <input
              :id="id + '-tag-' + tag.value"
              v-model="selectedTags"
              type="checkbox"
              :value="tag.value"
            />
            <label :for="id + '-tag-' + tag.value" role="button">
              <span>
                <span class="title">{{ tag.label }}</span>
                <span v-if="facetCount(tag.value) !== null" class="results-count">
                  {{ formatPlural(facetCount(tag.value), '1 result', '@count results') }}
                </span>
              </span>
            </label>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
export default {
  name: 'CategoryTagsFilter',
  props: {
    modelValue: {
      type: Array,
      required: true
    },
    id: {
      type: String,
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
    limitByCategoryTag: {
      type: Array,
      default: () => []
    },
    excludeByCategoryTag: {
      type: Array,
      default: () => []
    },
    variant: {
      type: String,
      default: 'card'
    }
  },
  data() {
    return {
      selectedTags: this.modelValue
    }
  },
  computed: {
    filteredTags() {
      return this.categoryTags.filter(tag => {
        const tagId = String(tag.value)
        if (this.limitByCategoryTag.length) {
          return this.limitByCategoryTag.includes(tagId)
        }
        if (this.excludeByCategoryTag.length) {
          return !this.excludeByCategoryTag.includes(tagId)
        }
        return true
      })
    }
  },
  watch: {
    modelValue() {
      this.selectedTags = this.modelValue
    },
    selectedTags() {
      this.$emit('update:modelValue', this.selectedTags)
    }
  },
  methods: {
    facetCount(value) {
      if (this.facets.length === 0) {
        return null
      }
      const facet = this.facets.find(f => String(f.id) === String(value))
      return facet && facet.count ? facet.count : 0
    }
  }
}
</script>

<style lang="scss">
.category-tags-filter-component {
  .ct-option {
    display: flex;
    height: 50px;

    input[type='checkbox'] {
      display: none;

      + label {
        cursor: pointer;
        display: flex;
        position: relative;
        margin: auto 0;
        padding-left: 50px;
        color: $af-black;
        font-size: 12px;
        line-height: 18px;
        font-weight: normal;

        &:before {
          content: '';
          display: block;
          position: absolute;
          left: 0;
          top: 0;
          height: 18px;
          width: 18px;
          margin: auto 16px;
          border: 2px solid $af-dark-gray;
          border-radius: 3px;
          align-self: center;
          background-color: $white;
        }
      }

      &:checked + label {
        &:before {
          background: $af-blue;
          border-width: 0;
        }

        &:after {
          content: '';
          display: block;
          position: absolute;
          left: 19px;
          top: 4px;
          margin-top: 0;
          width: 12px;
          height: 7px;
          border-left: 2px solid $white;
          border-bottom: 2px solid $white;
          transform: rotate(-45deg);
          align-self: center;
        }
      }
    }
  }
}
</style>
