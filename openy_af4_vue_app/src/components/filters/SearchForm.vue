<template>
  <div class="search-form-component">
    <input
      v-model="searchKeywords"
      type="text"
      class="form-control"
      :placeholder="t('Add keyword(s)')"
      @keyup.enter.prevent="runSearch"
    />
    <button class="btn" @click.stop.prevent="runSearch">
      <Icon icon="material-symbols:search" />
    </button>
  </div>
</template>

<script>
import { Icon } from '@iconify/vue'

export default {
  name: 'SearchForm',
  components: {
    Icon
  },
  props: {
    modelValue: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      searchKeywords: this.modelValue
    }
  },
  watch: {
    modelValue() {
      this.searchKeywords = this.modelValue
    }
  },
  methods: {
    runSearch() {
      this.$emit('update:modelValue', this.searchKeywords)
    }
  }
}
</script>

<style lang="scss">
.search-form-component {
  border: 1px solid $af-dark-gray;
  color: $af-dark-gray;
  display: flex;
  margin: 0 auto;
  border-radius: $af-border-radius;
  padding: 5px 5px 5px 10px;

  .form-control {
    border: none;
    font-size: 18px;
    line-height: 18px;
    height: 38px;
    padding: 0;
  }

  .btn {
    color: $white;
    width: 40px;
    padding: 6px 12px;
    border: none;
    font-size: 18px;
    line-height: 1;
    background-color: transparent;
    border-radius: $af-border-radius;

    svg {
      color: $af-black;
      height: 1.2rem;
      width: 1.2rem;
    }

    span {
      color: $af-black;
    }

    .fa {
      font-size: 18px;
    }
  }

  ::placeholder,
  ::-webkit-input-placeholder,
  :-moz-placeholder,
  ::-moz-placeholder,
  :-ms-input-placeholder {
    font-family: $af-font-body;
    font-weight: 400;
    line-height: 22px;
  }

  ::-webkit-input-placeholder {
    font-size: 18px;
    font-style: normal;
  }
  :-moz-placeholder {
    font-size: 18px;
    font-style: normal;
  }
  ::-moz-placeholder {
    font-size: 18px;
    font-style: normal;
  }
  :-ms-input-placeholder {
    font-size: 18px;
    font-style: normal;
  }
}
</style>
