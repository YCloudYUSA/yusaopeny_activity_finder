<template>
  <div class="foldable-input-component">
    <div class="foldable-title" :class="{ collapsed: !isOpen }" @click="isOpen = !isOpen">
      <span class="left">
        <span class="input" @click.stop="onInputClick"></span>
        <span class="title">
          {{ capitalize(label) }}
          <span v-if="counter > 0" class="subcounter">[+{{ counter }}]</span>
        </span>
      </span>
      <span class="right">
        <font-awesome-icon icon="chevron-down" />
        <font-awesome-icon icon="chevron-up" />
      </span>
    </div>
    <div v-show="isOpen" role="tabpanel" class="foldable-content">
      <slot />
    </div>
  </div>
</template>

<script>
export default {
  name: 'FoldableInput',
  props: {
    label: {
      type: String,
      default: 'Foldable Input'
    },
    collapseId: {
      type: String,
      default: 'foldable-input'
    },
    counter: {
      type: Number,
      default: 0
    }
  },
  data() {
    return { isOpen: false }
  },
  methods: {
    onInputClick() {
      this.$emit('input-click')
    }
  }
}
</script>

<style lang="scss">
.foldable-input-component {
  .foldable-title {
    border-bottom: unset;

    .input {
      position: relative;
      display: block;
      height: 18px;
      width: 18px;
      margin: auto 16px;
      border: 2px solid $af-dark-gray;
      border-radius: 3px;
    }

    .title {
      color: $af-black;
    }

    .subcounter {
      color: $af-blue;
      font-weight: bold;
      font-size: 12px;
    }

    .fa-chevron-up,
    .fa-chevron-down {
      width: 50px;
    }
  }

  .foldable-content {
    .option {
      margin-left: 35px;
    }
  }

  &.checked {
    .foldable-title {
      .input {
        background-color: $af-blue;
        border: none;

        &:after {
          content: '';
          display: block;
          position: absolute;
          left: 3px;
          top: 4px;
          width: 12px;
          height: 7px;
          border-left: 2px solid $white;
          border-bottom: 2px solid $white;
          transform: rotate(-45deg);
        }
      }
    }
  }

  &.semichecked {
    .foldable-title {
      .input {
        background-color: $af-blue;
        border: none;

        &:after {
          content: '';
          display: block;
          position: absolute;
          left: 4px;
          top: 8px;
          width: 10px;
          border-bottom: 2px solid $white;
        }
      }
    }
  }
}
</style>
