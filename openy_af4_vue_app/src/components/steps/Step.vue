<template>
  <div class="step-component">
    <div class="container">
      <div class="row">
        <div class="col-12 col-xs-12">
          <div class="top">
            <div class="controls">
              <div class="title">
                <slot name="title" />
              </div>
              <div class="buttons-desktop hidden-xs hidden-sm">
                <button
                  v-if="filtersSelected"
                  type="button"
                  class="btn btn-lg btn-next"
                  @click="onNext"
                >
                  {{ nextLabel }}
                </button>
                <button v-else type="button" class="btn btn-lg btn-skip" @click="onSkip">
                  {{ skipLabel }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <div class="row">
        <div class="col-12 col-xs-12">
          <slot :handle-sticky="handleSticky" />
        </div>
      </div>
    </div>

    <div ref="bottomDesktop" class="bottom-desktop hidden-xs hidden-sm full-width">
      <div :class="{ sticky: stickyDesktop }">
        <div class="container">
          <div class="row">
            <div class="col-12 col-xs-12">
              <div class="controls">
                <div class="title">
                  <slot name="title" />
                </div>
                <div class="buttons-desktop">
                  <button
                    v-if="filtersSelected"
                    type="button"
                    class="btn btn-lg btn-next"
                    @click="onNext"
                  >
                    {{ nextLabel }}
                  </button>
                  <button v-else type="button" class="btn btn-lg btn-skip" @click="onSkip">
                    {{ skipLabel }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Step',
  props: {
    filtersSelected: {
      type: Boolean,
      default: false
    },
    skipLabel: {
      type: String,
      default: 'Skip'
    },
    nextLabel: {
      type: String,
      default: 'Next'
    }
  },
  data() {
    return {
      sticky: false,
      stickyHeight: 125,
      stickyDesktop: false,
      stickyDesktopHeight: 95
    }
  },
  mounted() {
    this.handleSticky()
    window.addEventListener('scroll', this.handleSticky)
    window.addEventListener('resize', this.handleSticky)
  },
  beforeDestroy() {
    window.removeEventListener('scroll', this.handleSticky)
    window.removeEventListener('resize', this.handleSticky)
  },
  methods: {
    onSkip() {
      this.$emit('skip')
    },
    onNext() {
      this.$emit('next')
    },
    handleSticky() {
      const clientHeight = window.document.documentElement.clientHeight
      const rect = this.$refs.bottom ? this.$refs.bottom.getBoundingClientRect() : 0
      this.sticky = rect.top + this.stickyHeight >= clientHeight ? true : false
      const rectDesktop = this.$refs.bottomDesktop.getBoundingClientRect()
      this.stickyDesktop = rectDesktop.top + this.stickyDesktopHeight >= clientHeight ? true : false
    }
  }
}
</script>

<style lang="scss">
.step-component {
  .top {
    margin-top: 20px;
    margin-bottom: 20px;

    @include media-breakpoint-up('lg') {
      margin-top: 40px;
      margin-bottom: 40px;
    }
  }

  .controls {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-direction: column;
    color: $af-black;
    flex-wrap: wrap;
    gap: 24px;

    @include media-breakpoint-up('lg') {
      flex-direction: row;
      align-items: center;
    }

    .title {
      font-family: var(--ylb-font-family-cachet, Cachet), Verdana, sans-serif;
      font-size: 35px;
      line-height: 40px;

      @include media-breakpoint-up('lg') {
        font-size: 48px;
        line-height: 54px;
      }

      & > div {
        margin-top: 6px;
        font-size: 18px;
        line-height: 28px;
        font-weight: 400;
      }
    }

    .buttons-desktop {
      .btn {
        border-radius: 5px;
        font-weight: bolder;
        font-size: 18px;
        line-height: 46px;
        padding: 0 30px;
        white-space: nowrap;

        @include media-breakpoint-up('lg') {
          margin-top: 0;
        }

        &.btn-skip {
          background-color: $white;
          color: $af-blue;
          border: 2px solid $af-blue;
        }

        &.btn-next {
          background-color: $af-violet;
          color: $white;
          border: 2px solid $af-violet;
        }
      }
    }
  }

  .bottom-desktop {
    margin-top: 48px;
    background-color: $af-light-gray;

    @include media-breakpoint-up('lg') {
      margin-top: 40px;
      padding-top: 0;
    }

    .title {
      font-size: 18px;
      font-weight: 500;
      line-height: 28px;
    }

    .sticky {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      background-color: $white;
    }

    .controls {
      padding: 20px;
    }
  }

  .bottom {
    height: 55px;
    margin-top: 20px;
  }

  .buttons {
    width: 100%;

    &.sticky {
      position: fixed;
      bottom: 0;
      left: 0;
      border-bottom: 50px solid $white;
      z-index: 2;
    }

    .separator {
      opacity: 0.1;
    }

    .btn {
      width: 100%;
      font-weight: bold;
      font-size: 18px;
      line-height: 46px;
      padding: 0 10px;

      &.btn-skip {
        background-color: $white;
        color: $af-blue;
        border: 2px solid $af-blue;
      }

      &.btn-next {
        background-color: $af-violet;
        color: $white;
        border: 2px solid $af-violet;
      }
    }
  }
}
</style>
