<template>
  <div class="foldable-component">
    <div class="foldable-title" :class="{ collapsed: !isOpen }" @click="isOpen = !isOpen">
      <span class="left">
        <span class="title">
          {{ label }}
        </span>
      </span>
      <span class="right">
        <span v-if="counterMax > 0 && counter >= counterMax" class="max text-uppercase">
          {{ t('Max') }}
        </span>
        <span v-if="counter > 0" class="counter">
          {{ counter }}
        </span>
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
  name: 'Foldable',
  props: {
    label: {
      type: String,
      default: ''
    },
    collapseId: {
      type: String,
      default: 'foldable'
    },
    counter: {
      type: Number,
      default: 0
    },
    counterMax: {
      type: Number,
      default: 0
    }
  },
  data() {
    return { isOpen: false }
  }
}
</script>

<style lang="scss">
.foldable-component {
  .foldable-title {
    font-size: 14px;
    line-height: 50px;
    display: flex;
    justify-content: space-between;
    background-color: $af-light-gray;
    padding: 0 16px;
    border-bottom: solid 1px $af-border-gray;

    .left {
      display: flex;
      align-items: center;
      flex: auto;
      line-height: 50px;
    }

    .counter {
      margin: 10px;
      display: inline-block;
      background-color: $af-black;
      text-align: center;
      color: $white;
      border-radius: 5px;
      font-weight: bold;
      min-width: 30px;
      font-size: 12px;
      line-height: 30px;
    }

    .max {
      margin-left: 5px;
      color: $af-red;
      font-size: 10px;
      line-height: 50px;
      font-weight: bold;
    }

    &.collapsed .fa-chevron-up,
    &:not(.collapsed) .fa-chevron-down {
      display: none;
    }

    .fa-chevron-up,
    .fa-chevron-down {
      line-height: 50px;
      flex: 0 0 50px;
      text-align: center;
    }
  }

  .foldable-content {
    background-color: $af-lighter-gray2;

    .option {
      display: flex;
      height: 50px;

      input[type='checkbox'],
      input[type='radio'] {
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

        &:disabled + label {
          color: $af-dark-gray;

          &:before {
            border-color: $af-dark-gray;
          }
        }
      }
    }
  }
}
</style>
