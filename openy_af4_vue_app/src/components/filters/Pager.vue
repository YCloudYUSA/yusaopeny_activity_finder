<template>
  <div class="pager-component text-center">
    <span class="left">
      <a
        class="first-page"
        :class="{ disabled: value === 1 }"
        role="button"
        :title="t('First page')"
        @click="firstPage"
      >
        <span class="vertical-line">
          <i class="fa fa-chevron-left"></i>
        </span>
      </a>
      <a
        class="prev-page"
        :class="{ disabled: value === 1 }"
        role="button"
        :title="t('Previous page')"
        @click="prevPage"
      >
        <i class="fa fa-chevron-left"></i>
      </a>
    </span>
    <span class="center">
      {{ t('Page !selected of !total', { '!selected': selectedPage, '!total': totalPages }) }}
    </span>
    <span class="right">
      <a
        class="next-page"
        :class="{ disabled: value === totalPages }"
        role="button"
        :title="t('Next page')"
        @click="nextPage"
      >
        <i class="fa fa-chevron-right"></i>
      </a>
      <a
        class="last-page"
        :class="{ disabled: value === totalPages }"
        role="button"
        :title="t('Last page')"
        @click="lastPage"
      >
        <span class="vertical-line">
          <i class="fa fa-chevron-right"></i>
        </span>
      </a>
    </span>
  </div>
</template>

<script>
export default {
  name: 'Pager',
  props: {
    modelValue: {
      type: Number,
      required: true
    },
    totalPages: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      selectedPage: this.modelValue
    }
  },
  watch: {
    modelValue() {
      this.selectedPage = this.modelValue
    }
  },
  methods: {
    firstPage() {
      this.trackEvent('pagination', 'Click on first page', this.selectedPage)
      this.selectedPage = 1
      this.updateParent()
    },
    prevPage() {
      if (this.selectedPage === 1) {
        return
      }
      this.trackEvent('pagination', 'Click on previous page', this.selectedPage)
      this.selectedPage--
      this.updateParent()
    },
    nextPage() {
      if (this.selectedPage === this.totalPages) {
        return
      }
      this.trackEvent('pagination', 'Click on next page', this.selectedPage)
      this.selectedPage++
      this.updateParent()
    },
    lastPage() {
      this.trackEvent('pagination', 'Click on last page', this.selectedPage)
      this.selectedPage = this.totalPages
      this.updateParent()
    },
    updateParent() {
      this.$emit('update:modelValue', this.selectedPage)
    }
  }
}
</script>

<style lang="scss">
.pager-component {
  display: flex;
  justify-content: space-between;
  padding: 10px;
  color: $af-black;

  .center {
    font-size: 14px;
    line-height: 14px;
    padding: 10px 0;
  }

  a {
    padding: 10px;
    display: inline-block;
    color: $af-darker-gray;
    font-size: 16px;
    line-height: 16px;
    height: 35px;

    &.first-page {
      margin-right: 10px;

      .vertical-line {
        display: inline-block;
        height: 15px;
        border-left: 3px solid $af-dark-gray;
        padding-left: 3px;
      }

      &.disabled {
        .vertical-line {
          border-color: $af-lighter-gray;
        }
      }
    }

    &.last-page {
      margin-left: 10px;

      .vertical-line {
        display: inline-block;
        height: 15px;
        border-right: 3px solid $af-dark-gray;
        padding-right: 3px;
      }

      &.disabled {
        .vertical-line {
          border-color: $af-lighter-gray;
        }
      }
    }

    &.disabled {
      color: $af-lighter-gray;
      cursor: not-allowed;
    }
  }
}
</style>
