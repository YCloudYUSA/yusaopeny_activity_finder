<template>
  <div>
    <div
      v-if="visible"
      class="modal-backdrop show"
      style="position: fixed; top: 0; left: 0; z-index: 2040; width: 100vw; height: 100vh; background: rgba(0,0,0,1);"
      @click="close"
    ></div>
    <div
      v-if="visible"
      class="modal show af-modal"
      :class="{ 'af-flyout': flyout, 'af-narrow': narrow, 'af-responsive': responsive }"
      :id="id"
      role="dialog"
      tabindex="-1"
      style="display: block; position: fixed; top: 0; left: 0; z-index: 2050; width: 100%; height: 100%; overflow: hidden;"
      @click.self="close"
    >
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable af-modal-dialog">
        <div class="modal-content af-modal-content">
          <div class="modal-header af-modal-header">
            <slot name="modal-title">
              <h5 class="modal-title af-modal-title">{{ title }}</h5>
            </slot>
            <button type="button" class="close" @click="close" aria-label="Close">
              <!-- TODO(W5-P4): replace with Iconify icon when @iconify/vue migrated -->
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body af-modal-body">
            <slot />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Modal',
  props: {
    modelValue: { type: Boolean, default: false },
    id: { type: String, required: true },
    title: { type: String, default: '' },
    flyout: { type: Boolean, default: false },
    narrow: { type: Boolean, default: false },
    responsive: { type: Boolean, default: false }
  },
  data() {
    return { visible: this.modelValue }
  },
  watch: {
    modelValue(val) { this.visible = val },
    visible(val) {
      this.$emit('update:modelValue', val)
      document.body.style.overflow = val ? 'hidden' : ''
    }
  },
  mounted() {
    document.addEventListener('keydown', this.handleKeydown)
  },
  beforeUnmount() {
    document.removeEventListener('keydown', this.handleKeydown)
    document.body.style.overflow = ''
  },
  methods: {
    close() { this.visible = false },
    handleKeydown(e) {
      if (e.key === 'Escape' && this.visible) this.close()
    }
  }
}
</script>

<style lang="scss">
.af-modal {
  z-index: 2050;

  &.modal.show {
    opacity: 1;

    .af-modal-content {
      border-radius: 0.3rem;
    }

    .af-modal-header {
      padding: 1rem;
      border-top-left-radius: calc(0.3rem - 1px);
      border-top-right-radius: calc(0.3rem - 1px);

      .close {
        margin-top: -1rem;
      }
    }

    .af-modal-body {
      .row {
        margin-left: -15px;
        margin-right: -15px;
      }
    }

    .af-modal-dialog {
      display: flex;
      transform: none;
      margin: $modal-dialog-margin;
      width: auto;
      height: unset;

      @include media-breakpoint-up(sm) {
        margin: $modal-dialog-margin-y-sm-up auto;
      }
    }
  }

  &.modal.af-flyout {
    .af-modal-dialog {
      padding: 0;
      margin: auto;
      height: 100%;
      max-height: 100%;
      position: fixed;
      right: 0;
      width: 100%;
      max-width: 360px;
    }

    .af-modal-content {
      height: 100%;
      max-height: 100%;
    }

    &.show {
      .af-modal-dialog {
        right: 0;
      }
    }
  }

  &.modal.af-narrow {
    .af-modal-content {
      max-width: 340px;
      margin: 0 auto;
    }

    &.af-responsive {
      @include media-breakpoint-up('lg') {
        .af-modal-content {
          max-width: 680px;
          width: 680px;
        }
      }
    }
  }

  .af-modal-dialog {
    padding-left: 5px;
    padding-right: 5px;
  }

  .af-modal-header {
    padding: 0 0 0 1rem !important;
    background-color: $white;
    border-bottom: 1px solid $af-border-gray;
    align-items: center;

    .af-modal-title {
      color: $af-black;
      text-transform: uppercase;
      font-size: 18px;
      line-height: 27px;
    }

    .close {
      color: $af-dark-gray;
      font-size: 1.5rem;
      font-weight: 400;
      padding: 0;
      margin: 0 0 0 auto !important;
      width: 50px;
      height: 50px;
      background-color: $white;
      border: none;
      opacity: 1;
      display: flex;
      justify-content: center;
      align-items: center;
    }
  }

  .af-modal-body {
    padding: 0;
  }
}

.modal-backdrop.show {
  z-index: 2040;
}
</style>
