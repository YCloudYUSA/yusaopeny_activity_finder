<template>
  <div class="fieldset-component">
    <div class="fieldset-title" :class="{ collapsed: !isOpen }" @click="collapsible && (isOpen = !isOpen)">
      <span class="left">
        <span class="title">{{ label }}</span>
        <span v-if="counter" class="counter" :class="{ 'hide-counter': hideCounter }">
          {{ counter }}
        </span>
      </span>
      <span class="right">
        <span v-if="counterMax > 0 && counter >= counterMax" class="max text-uppercase">
          {{ t('Max') }}
        </span>
        <span
          v-else-if="counterOptions >= 0"
          class="options"
          :class="{ 'no-options': counterOptions === 0 }"
        >
          {{ formatPlural(counterOptions, '1 result', '@count results') }}
        </span>
        <span v-if="collapsible" class="icon">
          <Icon icon="material-symbols:add-circle-outline" class="circle-plus" />
          <Icon icon="material-symbols:do-not-disturb-on-outline" class="circle-minus" />
        </span>
        <span v-else-if="collapsible && counter && hideCounter" class="icon">
          <Icon icon="material-symbols:do-not-disturb-on-outline" class="circle-minus" />
        </span>
      </span>
    </div>
    <div
      v-if="collapsible"
      v-show="isOpen"
      role="tabpanel"
      class="fieldset-content"
    >
      <slot />
    </div>
    <div v-else class="fieldset-content">
      <slot />
    </div>
  </div>
</template>

<script>
import { Icon } from '@iconify/vue'

export default {
  name: 'Fieldset',
  components: {
    Icon
  },
  props: {
    label: {
      type: String,
      default: 'Fieldset'
    },
    collapsible: {
      type: Boolean,
      default: true
    },
    collapsed: {
      type: Boolean,
      default: true
    },
    collapseId: {
      type: String,
      default: 'fieldset'
    },
    counter: {
      type: Number,
      default: 0
    },
    counterMax: {
      type: Number,
      default: 0
    },
    hideCounter: {
      type: Boolean,
      default: false
    },
    counterOptions: {
      type: Number,
      default: -1
    },
    accordion: {
      type: String,
      default: ''
    },
    handleSticky: {
      type: Function,
      default: () => {}
    }
  },
  data() {
    return { isOpen: !this.collapsed }
  },
  watch: {
    isOpen() { this.handleSticky() }
  }
}
</script>

<style lang="scss">
.fieldset-component {
  & .fieldset-title :first-child {
    border-top-left-radius: $af-border-radius;
    border-top-right-radius: $af-border-radius;
  }

  .fieldset-title {
    border: 1px solid $af-border-gray;
    padding: 16px;
    display: flex;
    justify-content: space-between;
    min-height: 50px;

    .left,
    .right {
      display: flex;
      align-items: center;
    }

    .title {
      font-family: $af-font-cachet;
      font-size: 32px;
      line-height: 34px;
      font-weight: 500;
    }

    .counter {
      margin-left: 10px;
      display: inline-block;
      background-color: $af-blue;
      text-align: center;
      color: $white;
      border-radius: 5px;
      font-weight: 500;
      min-width: 30px;
      font-size: 12px;
      line-height: 30px;

      &.hide-counter {
        display: none;
      }
    }

    .right {
      white-space: nowrap;
      margin-left: 10px;
    }

    .max {
      color: $af-red;
      font-size: 10px;
      line-height: 15px;
      font-weight: 500;
    }

    .options {
      white-space: nowrap;
      font-size: 10px;
      line-height: 50px;

      &.no-options {
        color: $af-red;
        font-weight: 500;
      }
    }

    .icon {
      font-size: 24px;
      line-height: 50px;
      margin-left: 10px;
      padding: 0;
      position: relative;
      right: -5px;

      svg {
        color: $af-black;
        height: 1.2rem;
        width: 1.2rem;
      }
    }

    &.collapsed .circle-minus,
    &:not(.collapsed) .circle-plus {
      display: none;
    }

    &.collapsed {
      border-bottom-width: 0;
      .hide-counter {
        display: inline-block;
      }
    }
  }

  &:last-child {
    .fieldset-title {
      &.collapsed {
        border-bottom-width: 1px;
      }
    }
  }

  .fieldset-content {
    .options {
      background-color: $af-light-gray;
      padding: 10px 10px 5px;

      @include media-breakpoint-up('lg') {
        padding: 20px 20px 10px;
      }

      .row {
        margin-left: -5px;
        margin-right: -5px;
        display: flex;
        flex-wrap: wrap;

        @include media-breakpoint-up('md') {
          margin-left: -10px;
          margin-right: -10px;
        }
      }

      .option {
        margin-bottom: 5px;
        padding-left: 5px;
        padding-right: 5px;

        @include media-breakpoint-up('md') {
          padding-left: 10px;
          padding-right: 10px;
        }

        @include media-breakpoint-up('lg') {
          margin-bottom: 10px;
        }

        input[type='checkbox'],
        input[type='radio'] {
          display: none;

          & + label {
            position: relative;
            background-color: $white;
            border: 1px solid $af-border-gray;
            border-radius: 5px;
            display: flex;
            margin: 0;
            font-family: $af-font-body;
            height: 100%;
            padding: 10px;
            line-height: 28px;

            &:before {
              content: '';
              border: 1px solid $af-black;
            }

            .title {
              font-weight: 500;
              color: $af-blue;
              display: block;
            }

            .description {
              font-size: 14px;
              line-height: 20px;
              display: block;
            }

            .results-count {
              font-size: 14px;
              line-height: 20px;
              font-weight: normal;
              display: block;
            }
          }
        }

        &.check {
          label {
            &:before {
              color: $white;
              border-radius: 3px;
              margin: 6px 12px 12px 0;
              width: 16px;
              height: 16px;
              flex: 0 0 16px;
            }
          }

          input[type='checkbox']:checked + label,
          input[type='radio']:checked + label {
            &:before {
              border-color: $af-another-blue;
              background-color: $af-another-blue;
            }

            &:after {
              content: '';
              display: block;
              position: absolute;
              left: 12px;
              top: 18px;
              width: 12px;
              height: 7px;
              border-left: 2px solid $white;
              border-bottom: 2px solid $white;
              transform: rotate(-45deg);
            }
          }

          input[type='checkbox']:disabled + label,
          input[type='radio']:disabled + label {
            background-color: $af-light-gray;
            border-color: $af-border-gray;
            cursor: default;

            .title {
              color: $af-dark-gray;
            }

            .results-count {
              color: $af-red;
            }
          }
        }

        &.radio {
          margin-top: 0;

          label {
            padding-left: 0px;

            &:before {
              padding: 3px;
              border-radius: 50%;
              background-color: $white;
              background-clip: content-box;
              box-shadow: inset 0 0 0 3px $white;
              margin: 11px 15px 11px 5px;
              width: 20px;
              height: 20px;
              flex: 0 0 20px;
            }
          }

          input[type='checkbox']:checked + label,
          input[type='radio']:checked + label {
            &:before {
              border-color: $white;
              box-shadow: inset 0 0 0 3px $af-blue;
            }
          }

          input[type='checkbox']:disabled + label,
          input[type='radio']:disabled + label {
            background-color: $af-light-gray;
            border-color: $af-light-gray;
            cursor: default;

            &:before {
              box-shadow: inset 0 0 0 3px $af-light-gray;
              background-color: $af-light-gray;
            }

            .title {
              color: $af-dark-gray;
            }

            .results-count {
              color: $af-red;
              font-weight: bold;
            }
          }
        }
      }
    }
  }
}
</style>
